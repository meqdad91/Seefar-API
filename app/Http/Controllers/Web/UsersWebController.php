<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\GradeGrade;
use App\Models\User;
use App\Services\FilterService;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsersWebController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $filterService = app(FilterService::class);
        $projectService = app(ProjectService::class);

        $filters = $filterService->getFilters($request);
        $query = User::where('deleted', 0);

        $filterService->applyUserFilters($query, $filters, 'user');
        $filterService->applyDateFilter($query, $filters, 'timecreated');

        if ($cohortId = $request->query('cohort_id')) {
            $query->whereIn('id', function ($q) use ($cohortId) {
                $q->select('userid')->from('cohort_members')->where('cohortid', $cohortId);
            });
        }

        if (!empty($filters['course_id'])) {
            $query->whereIn('id', function ($q) use ($filters) {
                $q->select('ue.userid')
                    ->from('user_enrolments as ue')
                    ->join('enrol as e', 'e.id', '=', 'ue.enrolid')
                    ->where('e.courseid', $filters['course_id']);
            });
        } elseif (!empty($filters['project_id'])) {
            $courseIds = $projectService->getCourseIdsForProject($filters['project_id']);
            $query->whereIn('id', function ($q) use ($courseIds) {
                $q->select('ue.userid')
                    ->from('user_enrolments as ue')
                    ->join('enrol as e', 'e.id', '=', 'ue.enrolid')
                    ->whereIn('e.courseid', $courseIds);
            });
        }

        if (!empty($filters['completion_status'])) {
            if ($filters['completion_status'] === 'completed') {
                $query->whereIn('id', function ($q) use ($filters) {
                    $sub = $q->select('userid')->from('course_completions')->whereNotNull('timecompleted');
                    if (!empty($filters['course_id'])) {
                        $sub->where('course', $filters['course_id']);
                    }
                });
            } elseif ($filters['completion_status'] === 'in_progress') {
                $query->whereNotIn('id', function ($q) use ($filters) {
                    $sub = $q->select('userid')->from('course_completions')->whereNotNull('timecompleted');
                    if (!empty($filters['course_id'])) {
                        $sub->where('course', $filters['course_id']);
                    }
                });
            }
        }

        if ($search) {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('username', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('firstname', 'like', $like)
                    ->orWhere('lastname', 'like', $like);
            });
        }

        $users = $query->orderBy('id')->paginate(25)->withQueryString();
        return view('users.index', compact('users', 'search'));
    }

    public function show(Request $request, int $id)
    {
        $user = User::where('deleted', 0)->findOrFail($id);
        $scopedCourseId = $request->query('course_id') ? (int) $request->query('course_id') : null;

        $coursesQuery = Course::query()
            ->whereIn('id', function ($q) use ($id) {
                $q->select('e.courseid')
                    ->from('enrol as e')
                    ->join('user_enrolments as ue', 'ue.enrolid', '=', 'e.id')
                    ->where('ue.userid', $id);
            });

        $courses = $coursesQuery->get();

        // Recent grades ordered from LATEST to OLDEST with Course Name attached
        $gradesQuery = GradeGrade::with('item')
            ->join('grade_items as gi', 'gi.id', '=', 'grade_grades.itemid')
            ->leftJoin('course as c', 'c.id', '=', 'gi.courseid')
            ->where('grade_grades.userid', $id)
            ->select('grade_grades.*', 'c.fullname as course_name', 'gi.itemname', 'gi.grademax')
            ->orderByDesc('grade_grades.timemodified');

        if ($scopedCourseId) {
            $gradesQuery->where('gi.courseid', $scopedCourseId);
        }

        $grades = $gradesQuery->limit(20)->get();

        // Calculate Pre-Test, Post-Test, and Knowledge Gain % per course for student
        $courseGains = [];

        foreach ($courses as $c) {
            // Find quiz attempts for student in this course
            $attempts = DB::table('quiz_attempts as qa')
                ->join('quiz as q', 'q.id', '=', 'qa.quiz')
                ->where('qa.userid', $id)
                ->where('q.course', $c->id)
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

            // Fallback if not explicitly named pre/post: use earliest attempt as pre, latest as post
            if ($preScore === null && $attempts->count() >= 2) {
                $lastAtt = $attempts->first();
                $firstAtt = $attempts->last();
                $preScore = round(100 * $firstAtt->sumgrades / $firstAtt->max_grade, 1);
                $postScore = round(100 * $lastAtt->sumgrades / $lastAtt->max_grade, 1);
            }

            $gain = ($preScore !== null && $postScore !== null) ? round($postScore - $preScore, 1) : null;

            $completion = DB::table('course_completions')
                ->where('userid', $id)
                ->where('course', $c->id)
                ->first();

            $courseGains[] = (object) [
                'course' => $c,
                'is_completed' => !empty($completion->timecompleted),
                'completed_at' => !empty($completion->timecompleted) ? $completion->timecompleted : null,
                'pre_score' => $preScore,
                'post_score' => $postScore,
                'gain' => $gain,
                'attempts_count' => $attempts->count(),
            ];
        }

        return view('users.show', compact('user', 'courses', 'grades', 'courseGains', 'scopedCourseId'));
    }
}
