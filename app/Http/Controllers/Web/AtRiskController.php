<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AtRiskController extends Controller
{
    private const TTL = 600;
    private const INACTIVE_DAYS = 60;
    private const STALLED_DAYS = 60;
    private const LOW_GRADE_THRESHOLD = 0.5;

    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all'); // all|inactive|low_grade|stalled|no_quiz

        $data = Cache::remember('atrisk:overview', self::TTL, function () {
            $now = time();
            $inactiveCutoff = $now - self::INACTIVE_DAYS * 86400;
            $stalledCutoff = $now - self::STALLED_DAYS * 86400;

            // Population: active enrolled users
            $populationIds = DB::table('user')
                ->where('deleted', 0)
                ->where('suspended', 0)
                ->whereIn('id', function ($q) {
                    $q->select('userid')->from('user_enrolments');
                })
                ->pluck('id');

            $totalEnrolled = $populationIds->count();

            // Pull all population user records once
            $users = DB::table('user')
                ->whereIn('id', $populationIds)
                ->get(['id', 'username', 'firstname', 'lastname', 'email', 'lastaccess'])
                ->keyBy('id');

            // Per-user enrolment counts
            $enrolCounts = DB::table('user_enrolments as ue')
                ->join('enrol as e', 'e.id', '=', 'ue.enrolid')
                ->whereIn('ue.userid', $populationIds)
                ->select('ue.userid', DB::raw('COUNT(DISTINCT mdl_e.courseid) as c'))
                ->groupBy('ue.userid')
                ->pluck('c', 'userid');

            // Per-user stalled completions count
            $stalledByUser = DB::table('course_completions')
                ->whereIn('userid', $populationIds)
                ->whereNull('timecompleted')
                ->where('timeenrolled', '>', 0)
                ->where('timeenrolled', '<', $stalledCutoff)
                ->select('userid', DB::raw('COUNT(*) as c'))
                ->groupBy('userid')
                ->pluck('c', 'userid');

            // Per-user avg final grade (course-level only)
            $gradeRows = DB::table('grade_grades')
                ->join('grade_items', 'grade_items.id', '=', 'grade_grades.itemid')
                ->whereIn('grade_grades.userid', $populationIds)
                ->where('grade_items.itemtype', 'course')
                ->where('grade_items.grademax', '>', 0)
                ->whereNotNull('grade_grades.finalgrade')
                ->select(
                    'grade_grades.userid',
                    DB::raw('AVG(mdl_grade_grades.finalgrade / mdl_grade_items.grademax) as avg_pct'),
                    DB::raw('COUNT(*) as n')
                )
                ->groupBy('grade_grades.userid')
                ->get()
                ->keyBy('userid');

            // Per-user quiz attempt counts
            $quizCounts = DB::table('quiz_attempts')
                ->whereIn('userid', $populationIds)
                ->select('userid', DB::raw('COUNT(*) as c'))
                ->groupBy('userid')
                ->pluck('c', 'userid');

            // Score per user
            $scored = $populationIds->map(function ($id) use ($users, $enrolCounts, $stalledByUser, $gradeRows, $quizCounts, $inactiveCutoff) {
                $u = $users[$id];
                $enrolled = (int) ($enrolCounts[$id] ?? 0);
                $stalled = (int) ($stalledByUser[$id] ?? 0);
                $gradeRow = $gradeRows[$id] ?? null;
                $avgPct = $gradeRow ? (float) $gradeRow->avg_pct : null;
                $attempts = (int) ($quizCounts[$id] ?? 0);

                $flags = [];
                if (! $u->lastaccess || $u->lastaccess < $inactiveCutoff) {
                    $flags[] = 'inactive';
                }
                if ($avgPct !== null && $avgPct < self::LOW_GRADE_THRESHOLD) {
                    $flags[] = 'low_grade';
                }
                if ($stalled > 0) {
                    $flags[] = 'stalled';
                }
                if ($enrolled > 0 && $attempts === 0) {
                    $flags[] = 'no_quiz';
                }

                return (object) [
                    'id' => $u->id,
                    'username' => $u->username,
                    'name' => trim($u->firstname.' '.$u->lastname) ?: $u->username,
                    'email' => $u->email,
                    'lastaccess' => $u->lastaccess,
                    'enrolled_courses' => $enrolled,
                    'stalled_courses' => $stalled,
                    'avg_grade_pct' => $avgPct !== null ? round($avgPct * 100, 1) : null,
                    'graded_items' => $gradeRow ? (int) $gradeRow->n : 0,
                    'quiz_attempts' => $attempts,
                    'flags' => $flags,
                    'risk_score' => count($flags),
                ];
            });

            $atRisk = $scored->filter(fn ($u) => $u->risk_score > 0)->values();

            $counts = [
                'total_enrolled' => $totalEnrolled,
                'at_risk' => $atRisk->count(),
                'inactive' => $atRisk->filter(fn ($u) => in_array('inactive', $u->flags))->count(),
                'low_grade' => $atRisk->filter(fn ($u) => in_array('low_grade', $u->flags))->count(),
                'stalled' => $atRisk->filter(fn ($u) => in_array('stalled', $u->flags))->count(),
                'no_quiz' => $atRisk->filter(fn ($u) => in_array('no_quiz', $u->flags))->count(),
            ];

            $accessBuckets = [
                'Never'   => 0,
                '7d'      => 0,
                '8-30d'   => 0,
                '31-90d'  => 0,
                '90d+'    => 0,
            ];
            $now = time();
            foreach ($scored as $u) {
                if (! $u->lastaccess) { $accessBuckets['Never']++; }
                elseif ($u->lastaccess > $now - 7*86400) { $accessBuckets['7d']++; }
                elseif ($u->lastaccess > $now - 30*86400) { $accessBuckets['8-30d']++; }
                elseif ($u->lastaccess > $now - 90*86400) { $accessBuckets['31-90d']++; }
                else { $accessBuckets['90d+']++; }
            }

            return [
                'counts' => $counts,
                'accessBuckets' => $accessBuckets,
                'all' => $atRisk,
            ];
        });

        $list = $data['all'];
        if ($filter !== 'all') {
            $list = $list->filter(fn ($u) => in_array($filter, $u->flags))->values();
        }

        // Sort: most flags first, then oldest access
        $list = $list->sortByDesc(fn ($u) => [$u->risk_score, -1 * ($u->lastaccess ?: 0)])->values()->take(100);

        return view('atrisk', [
            'counts' => $data['counts'],
            'accessBuckets' => $data['accessBuckets'],
            'list' => $list,
            'filter' => $filter,
        ]);
    }
}
