<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\FilterService;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
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

        $thirtyDaysAgo = time() - 30 * 86400;
        $sevenDaysAgo = time() - 7 * 86400;

        // Base user query with demographic filters
        $userBaseQuery = DB::table('user as u')->where('u.deleted', 0);
        $this->filterService->applyUserFilters($userBaseQuery, $filters, 'u');
        $this->filterService->applyDateFilter($userBaseQuery, $filters, 'u.timecreated');

        $usersTotal = (clone $userBaseQuery)->count();
        $usersActive30d = (clone $userBaseQuery)->where('u.lastaccess', '>=', $thirtyDaysAgo)->count();
        $usersActive7d = (clone $userBaseQuery)->where('u.lastaccess', '>=', $sevenDaysAgo)->count();

        // Course counts
        $courseQuery = DB::table('course')->where('id', '!=', 1);
        if ($projectCourseIds !== null) {
            $courseQuery->whereIn('id', $projectCourseIds);
        }
        if (!empty($filters['course_id'])) {
            $courseQuery->where('id', $filters['course_id']);
        }
        $coursesTotal = (clone $courseQuery)->count();
        $coursesVisible = (clone $courseQuery)->where('visible', 1)->count();

        // Enrolments with filters
        $enrolQuery = DB::table('user_enrolments as ue')
            ->join('enrol as e', 'e.id', '=', 'ue.enrolid')
            ->join('user as u', 'u.id', '=', 'ue.userid')
            ->where('u.deleted', 0);

        if ($projectCourseIds !== null) {
            $enrolQuery->whereIn('e.courseid', $projectCourseIds);
        }
        if (!empty($filters['course_id'])) {
            $enrolQuery->where('e.courseid', $filters['course_id']);
        }
        $this->filterService->applyUserFilters($enrolQuery, $filters, 'u');
        $this->filterService->applyDateFilter($enrolQuery, $filters, 'ue.timecreated');

        $enrolmentsCount = $enrolQuery->count();

        // Completions
        $completionQuery = DB::table('course_completions as cc')
            ->join('user as u', 'u.id', '=', 'cc.userid');

        if ($projectCourseIds !== null) {
            $completionQuery->whereIn('cc.course', $projectCourseIds);
        }
        if (!empty($filters['course_id'])) {
            $completionQuery->where('cc.course', $filters['course_id']);
        }
        $this->filterService->applyUserFilters($completionQuery, $filters, 'u');
        $this->filterService->applyDateFilter($completionQuery, $filters, 'cc.timecompleted');

        $completionsStarted = (clone $completionQuery)->count();
        $completionsDone = (clone $completionQuery)->whereNotNull('cc.timecompleted')->count();

        // Quiz attempts and total quizzes
        $quizQuery = DB::table('quiz as q');
        if ($projectCourseIds !== null) {
            $quizQuery->whereIn('q.course', $projectCourseIds);
        }
        if (!empty($filters['course_id'])) {
            $quizQuery->where('q.course', $filters['course_id']);
        }
        $quizzesTotal = $quizQuery->count();

        $quizAttemptQuery = DB::table('quiz_attempts as qa')
            ->join('quiz as q', 'q.id', '=', 'qa.quiz')
            ->join('user as u', 'u.id', '=', 'qa.userid');

        if ($projectCourseIds !== null) {
            $quizAttemptQuery->whereIn('q.course', $projectCourseIds);
        }
        if (!empty($filters['course_id'])) {
            $quizAttemptQuery->where('q.course', $filters['course_id']);
        }
        $this->filterService->applyUserFilters($quizAttemptQuery, $filters, 'u');
        $this->filterService->applyDateFilter($quizAttemptQuery, $filters, 'qa.timestart');

        $quizAttemptsTotal = (clone $quizAttemptQuery)->count();
        $quizAttemptsFinished = (clone $quizAttemptQuery)->where('qa.state', 'finished')->count();

        $stats = [
            'users_total' => $usersTotal,
            'users_active_30d' => $usersActive30d,
            'users_active_7d' => $usersActive7d,
            'courses_total' => $coursesTotal,
            'courses_visible' => $coursesVisible,
            'enrolments' => $enrolmentsCount,
            'completions_started' => $completionsStarted,
            'completions_done' => $completionsDone,
            'quizzes_total' => $quizzesTotal,
            'quiz_attempts' => $quizAttemptsTotal,
            'quiz_attempts_finished' => $quizAttemptsFinished,
        ];

        // Top courses
        $rows = DB::table('user_enrolments as ue')
            ->join('enrol as e', 'e.id', '=', 'ue.enrolid')
            ->join('user as u', 'u.id', '=', 'ue.userid')
            ->where('u.deleted', 0);

        if ($projectCourseIds !== null) {
            $rows->whereIn('e.courseid', $projectCourseIds);
        }
        if (!empty($filters['course_id'])) {
            $rows->where('e.courseid', $filters['course_id']);
        }
        $this->filterService->applyUserFilters($rows, $filters, 'u');

        $topRows = $rows->select('e.courseid', DB::raw('COUNT(DISTINCT mdl_ue.userid) as enrolments'))
            ->groupBy('e.courseid')
            ->orderByDesc('enrolments')
            ->limit(8)
            ->get();

        $coursesMap = DB::table('course')
            ->whereIn('id', $topRows->pluck('courseid'))
            ->get(['id', 'fullname'])
            ->keyBy('id');

        $topCourses = $topRows
            ->filter(fn ($r) => isset($coursesMap[$r->courseid]))
            ->map(fn ($r) => (object) [
                'id' => $r->courseid,
                'fullname' => $coursesMap[$r->courseid]->fullname,
                'enrolments' => $r->enrolments,
            ])
            ->values();

        // Recent logins
        $loginsQuery = DB::table('user as u')
            ->where('u.deleted', 0)
            ->where('u.lastlogin', '>', 0);
        $this->filterService->applyUserFilters($loginsQuery, $filters, 'u');

        $recentLogins = $loginsQuery->orderByDesc('u.lastlogin')
            ->limit(8)
            ->get(['u.id', 'u.username', 'u.firstname', 'u.lastname', 'u.email', 'u.lastlogin']);

        return view('dashboard', compact('stats', 'topCourses', 'recentLogins'));
    }
}

