<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CohortsController extends Controller
{
    private const TTL = 600;
    private const ACTIVE_DAYS = 30;

    public function index()
    {
        $data = Cache::remember('cohorts:overview', self::TTL, function () {
            $cutoff = time() - self::ACTIVE_DAYS * 86400;

            $cohorts = DB::table('cohort')
                ->where('visible', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'idnumber', 'description']);

            $members = DB::table('cohort_members')->get(['cohortid', 'userid'])->groupBy('cohortid');

            $allMemberIds = $members->flatten(1)->pluck('userid')->unique();

            $usersById = DB::table('user')->whereIn('id', $allMemberIds)
                ->where('deleted', 0)
                ->get(['id', 'username', 'firstname', 'lastname', 'email', 'lastaccess', 'suspended'])
                ->keyBy('id');

            $gradeRows = DB::table('grade_grades')
                ->join('grade_items', 'grade_items.id', '=', 'grade_grades.itemid')
                ->whereIn('grade_grades.userid', $allMemberIds)
                ->where('grade_items.itemtype', 'course')
                ->where('grade_items.grademax', '>', 0)
                ->whereNotNull('grade_grades.finalgrade')
                ->select(
                    'grade_grades.userid',
                    DB::raw('AVG(mdl_grade_grades.finalgrade / mdl_grade_items.grademax) as avg_pct')
                )
                ->groupBy('grade_grades.userid')
                ->pluck('avg_pct', 'userid');

            $completionRows = DB::table('course_completions')
                ->whereIn('userid', $allMemberIds)
                ->select(
                    'userid',
                    DB::raw('COUNT(*) as started'),
                    DB::raw('SUM(CASE WHEN timecompleted IS NOT NULL THEN 1 ELSE 0 END) as completed')
                )
                ->groupBy('userid')
                ->get()
                ->keyBy('userid');

            $quizCounts = DB::table('quiz_attempts')
                ->whereIn('userid', $allMemberIds)
                ->select('userid', DB::raw('COUNT(*) as c'))
                ->groupBy('userid')
                ->pluck('c', 'userid');

            $cohortStats = $cohorts->map(function ($c) use ($members, $usersById, $gradeRows, $completionRows, $quizCounts, $cutoff) {
                $memberIds = ($members[$c->id] ?? collect())->pluck('userid');
                $valid = $memberIds->filter(fn ($id) => isset($usersById[$id]));

                $active = $valid->filter(fn ($id) => $usersById[$id]->lastaccess > $cutoff)->count();
                $suspended = $valid->filter(fn ($id) => $usersById[$id]->suspended)->count();

                $grades = $valid->map(fn ($id) => $gradeRows[$id] ?? null)->filter();
                $avgGrade = $grades->count() > 0 ? round($grades->avg() * 100, 1) : null;

                $started = $valid->sum(fn ($id) => $completionRows[$id]->started ?? 0);
                $completed = $valid->sum(fn ($id) => $completionRows[$id]->completed ?? 0);
                $completionRate = $started > 0 ? round(100 * $completed / $started, 1) : null;

                $attempts = $valid->sum(fn ($id) => $quizCounts[$id] ?? 0);

                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'idnumber' => $c->idnumber,
                    'members' => $valid->count(),
                    'active' => $active,
                    'active_pct' => $valid->count() > 0 ? round(100 * $active / $valid->count(), 1) : 0,
                    'suspended' => $suspended,
                    'avg_grade' => $avgGrade,
                    'graded_users' => $grades->count(),
                    'completions_started' => (int) $started,
                    'completions_done' => (int) $completed,
                    'completion_rate' => $completionRate,
                    'quiz_attempts' => (int) $attempts,
                    'attempts_per_member' => $valid->count() > 0 ? round($attempts / $valid->count(), 1) : 0,
                ];
            });

            return [
                'totalCohorts' => $cohorts->count(),
                'totalMembers' => $cohortStats->sum('members'),
                'avgCohortSize' => $cohorts->count() > 0 ? round($cohortStats->avg('members'), 1) : 0,
                'totalActive' => $cohortStats->sum('active'),
                'cohorts' => $cohortStats,
            ];
        });

        return view('cohorts', $data);
    }
}
