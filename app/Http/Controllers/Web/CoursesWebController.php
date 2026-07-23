<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoursesWebController extends Controller
{
    private const PASS_THRESHOLD = 0.5;

    public function index(Request $request)
    {
        $search = $request->query('search');
        $projectId = $request->query('project_id');
        $courseId = $request->query('course_id');

        $query = Course::with('categoryInfo')->where('id', '!=', 1)->where('visible', 1);

        if ($projectId) {
            $projectService = app(ProjectService::class);
            $courseIds = $projectService->getCourseIdsForProject($projectId);
            $query->whereIn('id', $courseIds);
        }

        if ($courseId) {
            $query->where('id', $courseId);
        }

        if ($search) {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('fullname', 'like', $like)
                    ->orWhere('shortname', 'like', $like);
            });
        }

        $courses = $query->orderBy('sortorder')->paginate(25)->withQueryString();
        return view('courses.index', compact('courses', 'search'));
    }

    public function show(int $id)
    {
        $course = Course::with('categoryInfo')->findOrFail($id);

        $studentCount = DB::table('user_enrolments as ue')
            ->join('enrol as e', 'e.id', '=', 'ue.enrolid')
            ->join('user as u', 'u.id', '=', 'ue.userid')
            ->where('e.courseid', $id)
            ->where('u.deleted', 0)
            ->distinct()
            ->count('u.id');

        $activityCount = DB::table('course_modules')->where('course', $id)->where('deletioninprogress', 0)->count();
        $quizCount = DB::table('quiz')->where('course', $id)->count();

        $avgGrade = DB::table('grade_items')
            ->join('grade_grades', 'grade_grades.itemid', '=', 'grade_items.id')
            ->where('grade_items.courseid', $id)
            ->where('grade_items.itemtype', 'course')
            ->whereNotNull('grade_grades.finalgrade')
            ->avg('grade_grades.finalgrade');

        $completionsStarted = DB::table('course_completions')->where('course', $id)->count();
        $completionsDone = DB::table('course_completions')->where('course', $id)->whereNotNull('timecompleted')->count();

        // Enrolled students
        $students = User::where('deleted', 0)
            ->whereIn('id', function ($q) use ($id) {
                $q->select('ue.userid')
                    ->from('user_enrolments as ue')
                    ->join('enrol as e', 'e.id', '=', 'ue.enrolid')
                    ->where('e.courseid', $id);
            })
            ->limit(10)
            ->get();

        // Completed Students list with Pre-grade, Post-grade, and % Knowledge Gain
        $completedRows = DB::table('course_completions as cc')
            ->join('user as u', 'u.id', '=', 'cc.userid')
            ->where('cc.course', $id)
            ->whereNotNull('cc.timecompleted')
            ->where('u.deleted', 0)
            ->select('u.id', 'u.username', 'u.firstname', 'u.lastname', 'u.email', 'cc.timecompleted')
            ->orderByDesc('cc.timecompleted')
            ->limit(20)
            ->get();

        $completedStudents = $completedRows->map(function ($u) use ($id) {
            $name = trim($u->firstname . ' ' . $u->lastname) ?: $u->username;

            // Fetch quiz attempts for this user in this course
            $attempts = DB::table('quiz_attempts as qa')
                ->join('quiz as q', 'q.id', '=', 'qa.quiz')
                ->where('qa.userid', $u->id)
                ->where('q.course', $id)
                ->where('qa.state', 'finished')
                ->whereNotNull('qa.sumgrades')
                ->where('q.sumgrades', '>', 0)
                ->select('q.name as quiz_name', 'qa.sumgrades', 'q.sumgrades as max_grade', 'qa.timestart')
                ->orderByDesc('qa.timestart')
                ->get();

            $preScore = null;
            $postScore = null;

            foreach ($attempts as $att) {
                $ratio = round(100 * $att->sumgrades / $att->max_grade, 1);
                $nameLower = strtolower($att->quiz_name);

                if (str_contains($nameLower, 'pre-test') || str_contains($nameLower, 'pre test') || str_contains($nameLower, 'baseline') || str_contains($nameLower, 'initial')) {
                    $preScore = $ratio;
                } elseif (str_contains($nameLower, 'post-test') || str_contains($nameLower, 'post test') || str_contains($nameLower, 'final') || str_contains($nameLower, 'midline')) {
                    $postScore = $ratio;
                }
            }

            if ($preScore === null && $attempts->count() >= 2) {
                $lastAtt = $attempts->first();
                $firstAtt = $attempts->last();
                $preScore = round(100 * $firstAtt->sumgrades / $firstAtt->max_grade, 1);
                $postScore = round(100 * $lastAtt->sumgrades / $lastAtt->max_grade, 1);
            }

            $gain = ($preScore !== null && $postScore !== null) ? round($postScore - $preScore, 1) : null;

            return (object) [
                'id' => $u->id,
                'name' => $name,
                'email' => $u->email,
                'completed_at' => $u->timecompleted,
                'pre_score' => $preScore,
                'post_score' => $postScore,
                'gain' => $gain,
            ];
        });

        // Course Quizzes Breakdown (Taker Volume: Overall, Pre-Test, Post-Test)
        $courseQuizzes = DB::table('quiz')->where('course', $id)->get();

        $quizBreakdown = $courseQuizzes->map(function ($q) {
            $attempts = DB::table('quiz_attempts')
                ->where('quiz', $q->id)
                ->get();

            $finished = $attempts->where('state', 'finished')->where('sumgrades', '!=', null);
            $uniqueTakers = $attempts->pluck('userid')->unique()->count();

            $avgScore = 0;
            $passRate = 0;

            if ($finished->count() > 0 && $q->sumgrades > 0) {
                $avgScore = round(100 * ($finished->avg('sumgrades') / $q->sumgrades), 1);
                $passCount = $finished->filter(fn ($att) => ($att->sumgrades / $q->sumgrades) >= self::PASS_THRESHOLD)->count();
                $passRate = round(100 * $passCount / $finished->count(), 1);
            }

            $nameLower = strtolower($q->name);
            $typeTag = 'Embedded Quiz';
            if (str_contains($nameLower, 'pre-test') || str_contains($nameLower, 'pre test')) {
                $typeTag = 'Pre-Test';
            } elseif (str_contains($nameLower, 'post-test') || str_contains($nameLower, 'post test') || str_contains($nameLower, 'final')) {
                $typeTag = 'Post-Test';
            }

            return (object) [
                'id' => $q->id,
                'name' => $q->name,
                'type_tag' => $typeTag,
                'total_attempts' => $attempts->count(),
                'finished_attempts' => $finished->count(),
                'unique_takers' => $uniqueTakers,
                'avg_score' => $avgScore,
                'pass_rate' => $passRate,
            ];
        });

        $activities = DB::table('course_modules as cm')
            ->join('modules as m', 'm.id', '=', 'cm.module')
            ->where('cm.course', $id)
            ->where('cm.deletioninprogress', 0)
            ->orderBy('cm.section')
            ->limit(15)
            ->get(['cm.id', 'cm.section', 'cm.visible', 'm.name as type']);

        return view('courses.show', compact(
            'course', 'studentCount', 'activityCount', 'quizCount', 'avgGrade',
            'completionsStarted', 'completionsDone', 'students', 'completedStudents',
            'quizBreakdown', 'activities'
        ));
    }
}
