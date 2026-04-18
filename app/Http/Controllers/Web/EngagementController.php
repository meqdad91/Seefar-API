<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EngagementController extends Controller
{
    private const TTL = 600; // 10 min

    public function index()
    {
        $cutoff = strtotime('-30 days', time());
        $cutoff7 = strtotime('-7 days', time());

        $data = Cache::remember('engagement:overview:30d', self::TTL, function () use ($cutoff, $cutoff7) {
            $base = fn () => DB::table('logstore_standard_log')->where('timecreated', '>=', $cutoff);

            $totalEvents = $base()->count();
            $uniqueUsers = $base()->where('userid', '>', 0)->distinct()->count('userid');
            $events7d = $base()->where('timecreated', '>=', $cutoff7)->count();

            $peakDay = $base()
                ->selectRaw('DATE(FROM_UNIXTIME(timecreated)) as day, COUNT(*) as events')
                ->groupBy('day')
                ->orderByDesc('events')
                ->first();

            $daily = $base()
                ->selectRaw('DATE(FROM_UNIXTIME(timecreated)) as day, COUNT(*) as events, COUNT(DISTINCT userid) as users')
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $hourly = $base()
                ->selectRaw('HOUR(FROM_UNIXTIME(timecreated)) as hour, COUNT(*) as events')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->keyBy('hour');

            $hourlyFilled = collect(range(0, 23))->map(fn ($h) => [
                'hour' => $h,
                'events' => (int) ($hourly[$h]->events ?? 0),
            ]);

            $topEvents = $base()
                ->selectRaw('eventname, COUNT(*) as count')
                ->groupBy('eventname')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(fn ($r) => [
                    'name' => $this->prettyEvent($r->eventname),
                    'raw' => $r->eventname,
                    'count' => (int) $r->count,
                ]);

            $topComponents = $base()
                ->selectRaw('component, COUNT(*) as count')
                ->groupBy('component')
                ->orderByDesc('count')
                ->limit(8)
                ->get()
                ->map(fn ($r) => [
                    'name' => $r->component,
                    'count' => (int) $r->count,
                ]);

            $byOrigin = $base()
                ->selectRaw('origin, COUNT(*) as count')
                ->groupBy('origin')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($r) => [
                    'name' => $r->origin ?: 'unknown',
                    'count' => (int) $r->count,
                ]);

            $topUsers = $base()
                ->where('userid', '>', 0)
                ->selectRaw('userid, COUNT(*) as count')
                ->groupBy('userid')
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

            return compact('totalEvents', 'uniqueUsers', 'events7d', 'peakDay', 'daily', 'hourlyFilled', 'topEvents', 'topComponents', 'byOrigin', 'topUsersEnriched');
        });

        return view('engagement', $data);
    }

    private function prettyEvent(string $eventname): string
    {
        $parts = explode('\\', trim($eventname, '\\'));
        $tail = end($parts);
        return ucwords(str_replace('_', ' ', $tail));
    }
}
