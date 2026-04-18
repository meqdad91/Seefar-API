<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('dashboard:overview', 600, function () {
            $thirtyDaysAgo = time() - 30 * 86400;
            $sevenDaysAgo = time() - 7 * 86400;

            return [
                'users_total' => DB::table('user')->where('deleted', 0)->count(),
                'users_active_30d' => DB::table('user')->where('deleted', 0)->where('lastaccess', '>=', $thirtyDaysAgo)->count(),
                'users_active_7d' => DB::table('user')->where('deleted', 0)->where('lastaccess', '>=', $sevenDaysAgo)->count(),
                'courses_total' => DB::table('course')->where('id', '!=', 1)->count(),
                'courses_visible' => DB::table('course')->where('id', '!=', 1)->where('visible', 1)->count(),
                'enrolments' => DB::table('user_enrolments')->count(),
                'completions_started' => DB::table('course_completions')->count(),
                'completions_done' => DB::table('course_completions')->whereNotNull('timecompleted')->count(),
                'quiz_attempts' => DB::table('quiz_attempts')->count(),
                'quiz_attempts_finished' => DB::table('quiz_attempts')->where('state', 'finished')->count(),
            ];
        });

        $topCourses = Cache::remember('dashboard:top_courses', 600, function () {
            $rows = DB::table('user_enrolments')
                ->join('enrol', 'enrol.id', '=', 'user_enrolments.enrolid')
                ->select('enrol.courseid', DB::raw('COUNT(DISTINCT mdl_user_enrolments.userid) as enrolments'))
                ->groupBy('enrol.courseid')
                ->orderByDesc('enrolments')
                ->limit(8)
                ->get();

            $courses = DB::table('course')
                ->whereIn('id', $rows->pluck('courseid'))
                ->where('visible', 1)
                ->where('id', '!=', 1)
                ->get(['id', 'fullname'])
                ->keyBy('id');

            return $rows
                ->filter(fn ($r) => isset($courses[$r->courseid]))
                ->map(fn ($r) => (object) [
                    'id' => $r->courseid,
                    'fullname' => $courses[$r->courseid]->fullname,
                    'enrolments' => $r->enrolments,
                ])
                ->values();
        });

        $recentLogins = Cache::remember('dashboard:recent_logins', 600, function () {
            return DB::table('user')
                ->where('deleted', 0)
                ->where('lastlogin', '>', 0)
                ->orderByDesc('lastlogin')
                ->limit(8)
                ->get(['id', 'username', 'firstname', 'lastname', 'email', 'lastlogin']);
        });

        return view('dashboard', compact('stats', 'topCourses', 'recentLogins'));
    }
}
