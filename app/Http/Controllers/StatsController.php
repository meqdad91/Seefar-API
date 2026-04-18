<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    private const TTL = 600; // 10 min

    public function overview()
    {
        $data = Cache::remember('stats:overview', self::TTL, function () {
            $thirtyDaysAgo = time() - 30 * 86400;
            $sevenDaysAgo = time() - 7 * 86400;

            return [
                'users' => [
                    'total' => DB::table('user')->where('deleted', 0)->count(),
                    'suspended' => DB::table('user')->where('deleted', 0)->where('suspended', 1)->count(),
                    'active_last_30d' => DB::table('user')->where('deleted', 0)->where('lastaccess', '>=', $thirtyDaysAgo)->count(),
                    'active_last_7d' => DB::table('user')->where('deleted', 0)->where('lastaccess', '>=', $sevenDaysAgo)->count(),
                ],
                'courses' => [
                    'total' => DB::table('course')->where('id', '!=', 1)->count(),
                    'visible' => DB::table('course')->where('id', '!=', 1)->where('visible', 1)->count(),
                    'categories' => DB::table('course_categories')->count(),
                ],
                'enrolments' => [
                    'total' => DB::table('user_enrolments')->count(),
                    'active' => DB::table('user_enrolments')->where('status', 0)->count(),
                ],
                'completions' => [
                    'started' => DB::table('course_completions')->count(),
                    'completed' => DB::table('course_completions')->whereNotNull('timecompleted')->count(),
                ],
                'quizzes' => [
                    'total' => DB::table('quiz')->count(),
                    'attempts_total' => DB::table('quiz_attempts')->count(),
                    'attempts_finished' => DB::table('quiz_attempts')->where('state', 'finished')->count(),
                ],
                'cohorts' => DB::table('cohort')->count(),
                'generated_at' => date(DATE_ATOM),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function course(int $id)
    {
        Course::findOrFail($id);

        $data = Cache::remember("stats:course:{$id}", self::TTL, function () use ($id) {
            $studentCount = DB::table('user_enrolments as ue')
                ->join('enrol as e', 'e.id', '=', 'ue.enrolid')
                ->join('user as u', 'u.id', '=', 'ue.userid')
                ->where('e.courseid', $id)
                ->where('u.deleted', 0)
                ->distinct()
                ->count('u.id');

            $activityCount = DB::table('course_modules')
                ->where('course', $id)
                ->where('deletioninprogress', 0)
                ->count();

            $gradeQuery = DB::table('grade_items')
                ->join('grade_grades', 'grade_grades.itemid', '=', 'grade_items.id')
                ->where('grade_items.courseid', $id)
                ->where('grade_items.itemtype', 'course')
                ->whereNotNull('grade_grades.finalgrade');

            $avgFinal = (clone $gradeQuery)->avg('grade_grades.finalgrade');
            $avgMax = (clone $gradeQuery)->avg('grade_items.grademax');
            $gradedUsers = (clone $gradeQuery)->count();

            $completionsStarted = DB::table('course_completions')->where('course', $id)->count();
            $completionsDone = DB::table('course_completions')->where('course', $id)->whereNotNull('timecompleted')->count();

            $quizAttempts = DB::table('quiz_attempts')
                ->join('quiz', 'quiz.id', '=', 'quiz_attempts.quiz')
                ->where('quiz.course', $id)
                ->count();

            return [
                'course_id' => $id,
                'students' => $studentCount,
                'activities' => $activityCount,
                'quizzes' => DB::table('quiz')->where('course', $id)->count(),
                'quiz_attempts' => $quizAttempts,
                'average_grade' => $avgFinal !== null ? (float) $avgFinal : null,
                'grade_max' => $avgMax !== null ? (float) $avgMax : null,
                'graded_users' => $gradedUsers,
                'completions' => [
                    'started' => $completionsStarted,
                    'completed' => $completionsDone,
                    'rate' => $completionsStarted > 0 ? round($completionsDone / $completionsStarted, 4) : null,
                ],
                'generated_at' => date(DATE_ATOM),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function userActivity(Request $request, int $id)
    {
        $user = User::where('deleted', 0)->findOrFail($id);
        $eventLimit = min((int) $request->query('events', 20), 100);

        $data = Cache::remember("stats:user:{$id}:activity:{$eventLimit}", self::TTL, function () use ($id, $user, $eventLimit) {
            $enrolledCourses = DB::table('user_enrolments as ue')
                ->join('enrol as e', 'e.id', '=', 'ue.enrolid')
                ->where('ue.userid', $id)
                ->distinct()
                ->count('e.courseid');

            $completed = DB::table('course_completions')
                ->where('userid', $id)
                ->whereNotNull('timecompleted')
                ->count();

            $avgGrade = DB::table('grade_grades as gg')
                ->join('grade_items as gi', 'gi.id', '=', 'gg.itemid')
                ->where('gg.userid', $id)
                ->where('gi.itemtype', 'course')
                ->whereNotNull('gg.finalgrade')
                ->avg('gg.finalgrade');

            $events = DB::table('logstore_standard_log')
                ->where('userid', $id)
                ->orderByDesc('id')
                ->limit($eventLimit)
                ->get(['id', 'eventname', 'action', 'target', 'courseid', 'timecreated', 'origin'])
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'event' => $e->eventname,
                    'action' => $e->action,
                    'target' => $e->target,
                    'course_id' => $e->courseid,
                    'origin' => $e->origin,
                    'occurred_at' => $e->timecreated ? date(DATE_ATOM, $e->timecreated) : null,
                ]);

            return [
                'user_id' => $id,
                'last_access_at' => $user->lastaccess ? date(DATE_ATOM, $user->lastaccess) : null,
                'last_login_at' => $user->lastlogin ? date(DATE_ATOM, $user->lastlogin) : null,
                'enrolled_courses' => $enrolledCourses,
                'completed_courses' => $completed,
                'average_grade' => $avgGrade !== null ? (float) $avgGrade : null,
                'recent_events' => $events,
                'generated_at' => date(DATE_ATOM),
            ];
        });

        return response()->json(['data' => $data]);
    }
}
