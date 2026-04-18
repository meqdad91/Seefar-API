<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class QuizzesController extends Controller
{
    private const TTL = 600;
    private const PASS_THRESHOLD = 0.5; // 50% of max sumgrades

    public function index()
    {
        $data = Cache::remember('quizzes:overview', self::TTL, function () {
            $totalQuizzes = DB::table('quiz')->count();
            $attemptsByState = DB::table('quiz_attempts')
                ->select('state', DB::raw('COUNT(*) as c'))
                ->groupBy('state')
                ->pluck('c', 'state');

            $totalAttempts = $attemptsByState->sum();
            $finishedAttempts = (int) ($attemptsByState['finished'] ?? 0);
            $inProgress = (int) ($attemptsByState['inprogress'] ?? 0);

            $finished = DB::table('quiz_attempts')
                ->join('quiz', 'quiz.id', '=', 'quiz_attempts.quiz')
                ->where('quiz_attempts.state', 'finished')
                ->whereNotNull('quiz_attempts.sumgrades')
                ->where('quiz.sumgrades', '>', 0)
                ->select(
                    'quiz_attempts.quiz as quiz_id',
                    'quiz_attempts.userid',
                    'quiz_attempts.sumgrades as score',
                    'quiz.sumgrades as max',
                    DB::raw('mdl_quiz_attempts.sumgrades / mdl_quiz.sumgrades as ratio')
                )
                ->get();

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

            $cutoff = time() - 30 * 86400;
            $daily = DB::table('quiz_attempts')
                ->where('timestart', '>=', $cutoff)
                ->selectRaw('DATE(FROM_UNIXTIME(timestart)) as day, COUNT(*) as attempts')
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $hardestRaw = $finished->groupBy('quiz_id')
                ->filter(fn ($g) => $g->count() >= 3)
                ->map(fn ($g, $qid) => (object) [
                    'quiz_id' => (int) $qid,
                    'attempts' => $g->count(),
                    'avg_pct' => round($g->avg('ratio') * 100, 1),
                ]);

            $hardest = $hardestRaw->sortBy('avg_pct')->take(8)->values();
            $easiest = $hardestRaw->sortByDesc('avg_pct')->take(8)->values();

            $mostAttempted = DB::table('quiz_attempts')
                ->select('quiz', DB::raw('COUNT(*) as c'))
                ->groupBy('quiz')
                ->orderByDesc('c')
                ->limit(8)
                ->get();

            $quizIds = collect()
                ->merge($hardest->pluck('quiz_id'))
                ->merge($easiest->pluck('quiz_id'))
                ->merge($mostAttempted->pluck('quiz'))
                ->unique();

            $quizMap = DB::table('quiz')->whereIn('id', $quizIds)->get(['id', 'name', 'course'])->keyBy('id');

            $hydrate = fn ($items, $idKey, $extraCols = []) => $items->map(function ($i) use ($quizMap, $idKey, $extraCols) {
                $qid = $i->$idKey;
                $row = ['id' => $qid, 'name' => $quizMap[$qid]->name ?? '(deleted)', 'course_id' => $quizMap[$qid]->course ?? null];
                foreach ($extraCols as $k) {
                    $row[$k] = $i->$k;
                }
                return $row;
            })->filter(fn ($r) => isset($quizMap[$r['id']]))->values();

            $hardestList = $hydrate($hardest, 'quiz_id', ['attempts', 'avg_pct']);
            $easiestList = $hydrate($easiest, 'quiz_id', ['attempts', 'avg_pct']);
            $mostList = $mostAttempted->map(fn ($i) => (object) ['quiz_id' => $i->quiz, 'c' => $i->c]);
            $mostList = $hydrate($mostList, 'quiz_id', ['c']);

            return [
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
            ];
        });

        return view('quizzes', $data);
    }
}
