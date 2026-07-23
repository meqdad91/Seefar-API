<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\FilterService;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectsController extends Controller
{
    protected ProjectService $projectService;
    protected FilterService $filterService;

    public function __construct(ProjectService $projectService, FilterService $filterService)
    {
        $this->projectService = $projectService;
        $this->filterService = $filterService;
    }

    public function index(Request $request)
    {
        $filters = $this->filterService->getFilters($request);
        $projectsList = $this->projectService->getProjects();

        $projects = [];

        foreach ($projectsList as $pId => $pData) {
            $courseIds = $pData['course_ids'];

            // Skip if filtering by specific project and this isn't it
            if (!empty($filters['project_id']) && $filters['project_id'] !== $pId) {
                continue;
            }

            // Skip if filtering by specific course and it's not in this project
            if (!empty($filters['course_id']) && !in_array($filters['course_id'], $courseIds)) {
                continue;
            }

            $enrolQuery = DB::table('user_enrolments as ue')
                ->join('enrol as e', 'e.id', '=', 'ue.enrolid')
                ->join('user as u', 'u.id', '=', 'ue.userid')
                ->whereIn('e.courseid', $courseIds)
                ->where('u.deleted', 0);

            $this->filterService->applyUserFilters($enrolQuery, $filters, 'u');
            $this->filterService->applyDateFilter($enrolQuery, $filters, 'ue.timecreated');

            $enrolmentCount = $enrolQuery->distinct('ue.userid')->count('ue.userid');

            $completionQuery = DB::table('course_completions as cc')
                ->join('user as u', 'u.id', '=', 'cc.userid')
                ->whereIn('cc.course', $courseIds)
                ->whereNotNull('cc.timecompleted');

            $this->filterService->applyUserFilters($completionQuery, $filters, 'u');
            $this->filterService->applyDateFilter($completionQuery, $filters, 'cc.timecompleted');

            $completionCount = $completionQuery->count();

            $quizQuery = DB::table('quiz_attempts as qa')
                ->join('quiz as q', 'q.id', '=', 'qa.quiz')
                ->join('user as u', 'u.id', '=', 'qa.userid')
                ->whereIn('q.course', $courseIds);

            $this->filterService->applyUserFilters($quizQuery, $filters, 'u');
            $this->filterService->applyDateFilter($quizQuery, $filters, 'qa.timestart');

            $quizAttemptsCount = $quizQuery->count();

            $projects[] = (object) [
                'id' => $pId,
                'name' => $pData['name'],
                'description' => $pData['description'],
                'course_count' => count($courseIds),
                'enrolments' => $enrolmentCount,
                'completions' => $completionCount,
                'quiz_attempts' => $quizAttemptsCount,
                'course_ids' => $courseIds,
            ];
        }

        return view('projects.index', compact('projects'));
    }

    public function show(Request $request, string $id)
    {
        $project = $this->projectService->getProjectById($id);
        if (!$project) {
            abort(404, 'Project not found.');
        }

        $courseIds = $project['course_ids'];
        $courses = Course::with('categoryInfo')
            ->whereIn('id', $courseIds)
            ->get();

        $enrolments = DB::table('user_enrolments as ue')
            ->join('enrol as e', 'e.id', '=', 'ue.enrolid')
            ->whereIn('e.courseid', $courseIds)
            ->distinct('ue.userid')
            ->count('ue.userid');

        $completions = DB::table('course_completions')
            ->whereIn('course', $courseIds)
            ->whereNotNull('timecompleted')
            ->count();

        $quizAttempts = DB::table('quiz_attempts as qa')
            ->join('quiz as q', 'q.id', '=', 'qa.quiz')
            ->whereIn('q.course', $courseIds)
            ->count();

        return view('projects.show', compact('project', 'courses', 'enrolments', 'completions', 'quizAttempts'));
    }
}
