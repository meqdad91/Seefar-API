<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LanguagesController extends Controller
{
    private const TTL = 600;

    public function index()
    {
        $data = Cache::remember('languages:overview', self::TTL, function () {
            $topCats = DB::table('course_categories')
                ->where('parent', 0)
                ->where('visible', 1)
                ->orderBy('sortorder')
                ->get(['id', 'name', 'path']);

            $allCats = DB::table('course_categories')->get(['id', 'name', 'path'])->keyBy('id');

            $stats = $topCats->map(function ($cat) use ($allCats) {
                // Collect this category + all descendant category IDs
                $catIds = $allCats
                    ->filter(fn ($c) => $c->id === $cat->id || str_starts_with($c->path, "/{$cat->id}/"))
                    ->pluck('id');

                $courseIds = DB::table('course')
                    ->whereIn('category', $catIds)
                    ->where('id', '!=', 1)
                    ->pluck('id');

                if ($courseIds->isEmpty()) {
                    return $this->emptyStats($cat);
                }

                $userIds = DB::table('user_enrolments as ue')
                    ->join('enrol as e', 'e.id', '=', 'ue.enrolid')
                    ->whereIn('e.courseid', $courseIds)
                    ->distinct()
                    ->pluck('ue.userid');

                $activeUsers = DB::table('user')
                    ->whereIn('id', $userIds)
                    ->where('deleted', 0)
                    ->where('lastaccess', '>=', time() - 30 * 86400)
                    ->count();

                $completionRows = DB::table('course_completions')
                    ->whereIn('course', $courseIds)
                    ->select(
                        DB::raw('COUNT(*) as started'),
                        DB::raw('SUM(CASE WHEN timecompleted IS NOT NULL THEN 1 ELSE 0 END) as completed')
                    )
                    ->first();

                $started = (int) ($completionRows->started ?? 0);
                $completed = (int) ($completionRows->completed ?? 0);
                $completionRate = $started > 0 ? round(100 * $completed / $started, 1) : null;

                $gradeRow = DB::table('grade_grades')
                    ->join('grade_items', 'grade_items.id', '=', 'grade_grades.itemid')
                    ->whereIn('grade_items.courseid', $courseIds)
                    ->where('grade_items.itemtype', 'course')
                    ->where('grade_items.grademax', '>', 0)
                    ->whereNotNull('grade_grades.finalgrade')
                    ->select(
                        DB::raw('AVG(mdl_grade_grades.finalgrade / mdl_grade_items.grademax) as avg_pct'),
                        DB::raw('COUNT(*) as n')
                    )
                    ->first();

                $avgGrade = $gradeRow && $gradeRow->avg_pct ? round($gradeRow->avg_pct * 100, 1) : null;
                $gradedCount = (int) ($gradeRow->n ?? 0);

                $quizCount = DB::table('quiz')->whereIn('course', $courseIds)->count();
                $quizAttempts = DB::table('quiz_attempts')
                    ->join('quiz', 'quiz.id', '=', 'quiz_attempts.quiz')
                    ->whereIn('quiz.course', $courseIds)
                    ->count();

                $activities = DB::table('course_modules')
                    ->whereIn('course', $courseIds)
                    ->where('deletioninprogress', 0)
                    ->count();

                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'short' => $this->shortLabel($cat->name),
                    'courses' => $courseIds->count(),
                    'activities' => $activities,
                    'enrolled' => $userIds->count(),
                    'active_30d' => $activeUsers,
                    'active_pct' => $userIds->count() > 0 ? round(100 * $activeUsers / $userIds->count(), 1) : 0,
                    'completions_started' => $started,
                    'completions_done' => $completed,
                    'completion_rate' => $completionRate,
                    'avg_grade' => $avgGrade,
                    'graded_count' => $gradedCount,
                    'quizzes' => $quizCount,
                    'quiz_attempts' => $quizAttempts,
                ];
            })->values();

            return [
                'totalCategories' => $topCats->count(),
                'totalCourses' => $stats->sum('courses'),
                'totalEnrolled' => $stats->sum('enrolled'),
                'totalCompleted' => $stats->sum('completions_done'),
                'overallCompletionRate' => $stats->sum('completions_started') > 0
                    ? round(100 * $stats->sum('completions_done') / $stats->sum('completions_started'), 1)
                    : 0,
                'languages' => $stats,
            ];
        });

        return view('languages', $data);
    }

    private function emptyStats($cat): array
    {
        return [
            'id' => $cat->id, 'name' => $cat->name, 'short' => $this->shortLabel($cat->name),
            'courses' => 0, 'activities' => 0, 'enrolled' => 0, 'active_30d' => 0, 'active_pct' => 0,
            'completions_started' => 0, 'completions_done' => 0, 'completion_rate' => null,
            'avg_grade' => null, 'graded_count' => 0, 'quizzes' => 0, 'quiz_attempts' => 0,
        ];
    }

    private function shortLabel(string $name): string
    {
        if (preg_match('/\(([^)]+)\)/', $name, $m)) {
            return $m[1];
        }
        return $name;
    }
}
