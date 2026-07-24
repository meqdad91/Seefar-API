<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\FilterService;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizzesController extends Controller
{
    private const PASS_THRESHOLD = 0.5; // 50% of max sumgrades

    protected FilterService $filterService;
    protected ProjectService $projectService;

    public function __construct(FilterService $filterService, ProjectService $projectService)
    {
        $this->filterService = $filterService;
        $this->projectService = $projectService;
    }

    public function index(Request $request)
    {
        $filters = $this->filterService->getFilters($request);

        // Resolve course IDs if filtered by Project
        $projectCourseIds = null;
        if (!empty($filters['project_id'])) {
            $projectCourseIds = $this->projectService->getCourseIdsForProject($filters['project_id']);
        }

        // Base quiz query
        $quizQuery = DB::table('quiz as q');
        if (!empty($filters['course_id'])) {
            $quizQuery->where('q.course', $filters['course_id']);
        } elseif ($projectCourseIds !== null) {
            $quizQuery->whereIn('q.course', $projectCourseIds);
        }
        $totalQuizzes = $quizQuery->count();

        // Attempts query
        $attemptQuery = DB::table('quiz_attempts as qa')
            ->join('quiz as q', 'q.id', '=', 'qa.quiz');

        if (!empty($filters['course_id'])) {
            $attemptQuery->where('q.course', $filters['course_id']);
        } elseif ($projectCourseIds !== null) {
            $attemptQuery->whereIn('q.course', $projectCourseIds);
        }

        if (!empty($filters['country']) || !empty($filters['sex']) || !empty($filters['age_group'])) {
            $attemptQuery->join('user as u', 'u.id', '=', 'qa.userid');
            $this->filterService->applyUserFilters($attemptQuery, $filters, 'u');
        }

        $this->filterService->applyDateFilter($attemptQuery, $filters, 'qa.timestart');

        $attemptsByStateRaw = (clone $attemptQuery)
            ->select('qa.state', DB::raw('COUNT(*) as c'))
            ->groupBy('qa.state')
            ->pluck('c', 'qa.state');

        $attemptsByState = [
            'Finished' => (int) ($attemptsByStateRaw['finished'] ?? 0),
            'In Progress' => (int) ($attemptsByStateRaw['inprogress'] ?? 0),
            'Overdue' => (int) ($attemptsByStateRaw['overdue'] ?? 0),
            'Abandoned' => (int) ($attemptsByStateRaw['abandoned'] ?? 0),
        ];

        $totalAttempts = array_sum($attemptsByState);
        $finishedAttempts = $attemptsByState['Finished'];
        $inProgress = $attemptsByState['In Progress'];

        $prefix = DB::getTablePrefix();

        // Finished attempts with ratios
        $finishedQuery = (clone $attemptQuery)
            ->where('qa.state', 'finished')
            ->whereNotNull('qa.sumgrades')
            ->where('q.sumgrades', '>', 0)
            ->select(
                'qa.quiz as quiz_id',
                'qa.userid',
                'qa.sumgrades as score',
                'q.sumgrades as max',
                'q.name as quiz_name',
                'q.course as course_id',
                DB::raw("{$prefix}qa.sumgrades / {$prefix}q.sumgrades as ratio")
            );

        $finished = $finishedQuery->get();

        $avgScorePct = $finished->avg('ratio') * 100;
        $passCount = $finished->filter(fn ($a) => $a->ratio >= self::PASS_THRESHOLD)->count();
        $passRate = $finished->count() > 0 ? round(100 * $passCount / $finished->count(), 1) : 0;

        $buckets = ['0–20%' => 0, '20–40%' => 0, '40–60%' => 0, '60–80%' => 0, '80–100%' => 0];
        foreach ($finished as $a) {
            $p = $a->ratio * 100;
            if ($p < 20) { $buckets['0–20%']++; }
            elseif ($p < 40) { $buckets['20–40%']++; }
            elseif ($p < 60) { $buckets['40–60%']++; }
            elseif ($p < 80) { $buckets['60–80%']++; }
            else { $buckets['80–100%']++; }
        }

        // Daily attempts timeline
        $cutoff = time() - 30 * 86400;
        $daily = (clone $attemptQuery)
            ->where('qa.timestart', '>=', $cutoff)
            ->selectRaw("DATE(FROM_UNIXTIME({$prefix}qa.timestart)) as day, COUNT(*) as attempts")
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Quiz ranking list preparation
        $hardestRaw = $finished->groupBy('quiz_id')
            ->filter(fn ($g) => $g->count() >= 1)
            ->map(fn ($g, $qid) => (object) [
                'quiz_id' => (int) $qid,
                'attempts' => $g->count(),
                'avg_pct' => round($g->avg('ratio') * 100, 1),
            ]);

        $hardest = $hardestRaw->sortBy('avg_pct')->take(8)->values();
        $easiest = $hardestRaw->sortByDesc('avg_pct')->take(8)->values();

        $mostAttempted = (clone $attemptQuery)
            ->select('qa.quiz', DB::raw('COUNT(*) as c'))
            ->groupBy('qa.quiz')
            ->orderByDesc('c')
            ->limit(8)
            ->get();

        $quizIds = collect()
            ->merge($hardest->pluck('quiz_id'))
            ->merge($easiest->pluck('quiz_id'))
            ->merge($mostAttempted->pluck('quiz'))
            ->unique();

        // Hydrate quiz names WITH course name prefix to ensure unique identifiers
        $quizMapRaw = DB::table('quiz as q')
            ->leftJoin('course as c', 'c.id', '=', 'q.course')
            ->whereIn('q.id', $quizIds)
            ->get(['q.id', 'q.name', 'q.course', 'c.fullname as course_fullname'])
            ->keyBy('id');

        $hydrate = fn ($items, $idKey, $extraCols = []) => $items->map(function ($i) use ($quizMapRaw, $idKey, $extraCols) {
            $qid = $i->$idKey;
            $qObj = $quizMapRaw[$qid] ?? null;

            if (!$qObj) {
                return null;
            }

            $courseName = $qObj->course_fullname ? trim($qObj->course_fullname) : 'Course #' . $qObj->course;
            $quizName = trim($qObj->name);

            // Disambiguated Quiz Name
            $fullName = str_contains(strtolower($quizName), strtolower($courseName))
                ? $quizName
                : "{$courseName} — {$quizName}";

            $row = [
                'id' => $qid,
                'name' => $fullName,
                'raw_name' => $quizName,
                'course_name' => $courseName,
                'course_id' => $qObj->course,
            ];

            foreach ($extraCols as $k) {
                $row[$k] = $i->$k;
            }
            return $row;
        })->filter()->values();

        $hardestList = $hydrate($hardest, 'quiz_id', ['attempts', 'avg_pct']);
        $easiestList = $hydrate($easiest, 'quiz_id', ['attempts', 'avg_pct']);
        $mostList = $mostAttempted->map(fn ($i) => (object) ['quiz_id' => $i->quiz, 'c' => $i->c]);
        $mostList = $hydrate($mostList, 'quiz_id', ['c']);

        // Categorize Quizzes by Quiz Type (Baseline, Midline, Pre-Test, Post-Test, Embedded)
        $allQuizzesInDb = DB::table('quiz as q')
            ->leftJoin('course as c', 'c.id', '=', 'q.course')
            ->select('q.id', 'q.name', 'q.course', 'c.fullname as course_name')
            ->get();

        $typesDef = [
            'baseline' => ['name' => 'Baseline Assessment', 'desc' => 'Program-level baseline tests before course start'],
            'midline'  => ['name' => 'Midline Assessment',  'desc' => 'Mid-program progress evaluation tests'],
            'pretest'  => ['name' => 'Pre-Test',            'desc' => 'Course entry tests embedded at course start'],
            'posttest' => ['name' => 'Post-Test / Final',   'desc' => 'Course exit tests embedded at course end'],
            'embedded' => ['name' => 'In-Course Quiz',      'desc' => 'Interactive module quizzes embedded within courses'],
        ];

        $quizTypesSummary = [];

        foreach ($typesDef as $typeKey => $meta) {
            $typeQuizzes = $allQuizzesInDb->filter(function ($q) use ($typeKey) {
                $n = strtolower($q->name);
                if ($typeKey === 'baseline') { return str_contains($n, 'baseline'); }
                if ($typeKey === 'midline')  { return str_contains($n, 'midline'); }
                if ($typeKey === 'pretest')  { return str_contains($n, 'pre-test') || str_contains($n, 'pre test'); }
                if ($typeKey === 'posttest') { return str_contains($n, 'post-test') || str_contains($n, 'post test') || str_contains($n, 'final'); }
                return !str_contains($n, 'baseline') && !str_contains($n, 'midline') && !str_contains($n, 'pre-test') && !str_contains($n, 'pre test') && !str_contains($n, 'post-test') && !str_contains($n, 'post test') && !str_contains($n, 'final');
            });

            $tQids = $typeQuizzes->pluck('id')->toArray();
            $tFinished = $finished->whereIn('quiz_id', $tQids);
            $tPassCount = $tFinished->filter(fn ($a) => $a->ratio >= self::PASS_THRESHOLD)->count();
            $tPassRate = $tFinished->count() > 0 ? round(100 * $tPassCount / $tFinished->count(), 1) : 0;

            $quizTypesSummary[] = (object) [
                'key' => $typeKey,
                'name' => $meta['name'],
                'desc' => $meta['desc'],
                'quiz_count' => count($tQids),
                'attempts' => $tFinished->count(),
                'avg_score' => $tFinished->count() > 0 ? round($tFinished->avg('ratio') * 100, 1) : 0,
                'pass_rate' => $tPassRate,
            ];
        }

        return view('quizzes', [
            'totalQuizzes' => $totalQuizzes,
            'totalAttempts' => $totalAttempts,
            'finishedAttempts' => $finishedAttempts,
            'inProgress' => $inProgress,
            'avgScorePct' => $avgScorePct ? round($avgScorePct, 1) : 0,
            'passRate' => $passRate,
            'passCount' => $passCount,
            'gradedAttempts' => $finished->count(),
            'attemptsByState' => $attemptsByState,
            'buckets' => $buckets,
            'daily' => $daily,
            'hardestList' => $hardestList,
            'easiestList' => $easiestList,
            'mostList' => $mostList,
            'quizTypesSummary' => $quizTypesSummary,
        ]);
    }
}
