<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\FilterService;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EngagementController extends Controller
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

        $cutoff = strtotime('-30 days', time());
        $cutoff7 = strtotime('-7 days', time());

        // Resolve course IDs if filtered by Project
        $projectCourseIds = null;
        if (!empty($filters['project_id'])) {
            $projectCourseIds = $this->projectService->getCourseIdsForProject($filters['project_id']);
        }

        $base = function () use ($filters, $projectCourseIds, $cutoff) {
            $query = DB::table('logstore_standard_log as l');

            if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
                $this->filterService->applyDateFilter($query, $filters, 'l.timecreated');
            } else {
                $query->where('l.timecreated', '>=', $cutoff);
            }

            if (!empty($filters['origin'])) {
                $query->where('l.origin', $filters['origin']);
            }

            if (!empty($filters['course_id'])) {
                $query->where('l.courseid', $filters['course_id']);
            } elseif ($projectCourseIds !== null) {
                $query->whereIn('l.courseid', $projectCourseIds);
            }

            if (!empty($filters['country']) || !empty($filters['sex']) || !empty($filters['age_group'])) {
                $query->join('user as u', 'u.id', '=', 'l.userid');
                $this->filterService->applyUserFilters($query, $filters, 'u');
            }

            return $query;
        };

        $totalEvents = $base()->count();
        $uniqueUsers = $base()->where('l.userid', '>', 0)->distinct('l.userid')->count('l.userid');
        $events7d = $base()->where('l.timecreated', '>=', $cutoff7)->count();

        $peakDay = $base()
            ->selectRaw('DATE(FROM_UNIXTIME(l.timecreated)) as day, COUNT(*) as events')
            ->groupBy('day')
            ->orderByDesc('events')
            ->first();

        $daily = $base()
            ->selectRaw('DATE(FROM_UNIXTIME(l.timecreated)) as day, COUNT(*) as events, COUNT(DISTINCT l.userid) as users')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $hourly = $base()
            ->selectRaw('HOUR(FROM_UNIXTIME(l.timecreated)) as hour, COUNT(*) as events')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $hourlyFilled = collect(range(0, 23))->map(fn ($h) => [
            'hour' => $h,
            'events' => (int) ($hourly[$h]->events ?? 0),
        ]);

        $topEvents = $base()
            ->selectRaw('l.eventname, COUNT(*) as count')
            ->groupBy('l.eventname')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'name' => $this->prettyEvent($r->eventname),
                'raw' => $r->eventname,
                'count' => (int) $r->count,
            ]);

        $topComponents = $base()
            ->selectRaw('l.component, COUNT(*) as count')
            ->groupBy('l.component')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'name' => $r->component,
                'count' => (int) $r->count,
            ]);

        $byOrigin = $base()
            ->selectRaw('l.origin, COUNT(*) as count')
            ->groupBy('l.origin')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => [
                'name' => $r->origin ?: 'unknown',
                'count' => (int) $r->count,
            ]);

        $topUsers = $base()
            ->where('l.userid', '>', 0)
            ->selectRaw('l.userid, COUNT(*) as count')
            ->groupBy('l.userid')
            ->orderByDesc('count')
            ->limit(8)
            ->get();

        $userIds = $topUsers->pluck('userid');
        $userMap = DB::table('user')->whereIn('id', $userIds)->get(['id', 'username', 'firstname', 'lastname'])->keyBy('id');

        $topUsersEnriched = $topUsers->map(fn ($r) => [
            'id' => $r->userid,
            'name' => isset($userMap[$r->userid])
                ? (trim($userMap[$r->userid]->firstname.' '.$userMap[$r->userid]->lastname) ?: $userMap[$r->userid]->username)
                : '#'.$r->userid,
            'username' => $userMap[$r->userid]->username ?? null,
            'count' => (int) $r->count,
        ]);

        $data = compact('totalEvents', 'uniqueUsers', 'events7d', 'peakDay', 'daily', 'hourlyFilled', 'topEvents', 'topComponents', 'byOrigin', 'topUsersEnriched');

        return view('engagement', $data);
    }

    private function prettyEvent(string $eventname): string
    {
        $parts = explode('\\', trim($eventname, '\\'));
        $tail = end($parts);
        return ucwords(str_replace('_', ' ', $tail));
    }
}
