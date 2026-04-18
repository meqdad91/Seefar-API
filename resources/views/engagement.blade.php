@extends('layouts.app', ['title' => 'Engagement', 'subtitle' => 'Activity over the last 30 days'])

@section('content')
@php
    $cards = [
        ['label' => 'Total events',    'value' => number_format($totalEvents),                    'sub' => 'last 30 days',                                          'icon' => 'fire',     'tone' => 'amber'],
        ['label' => 'Unique users',    'value' => number_format($uniqueUsers),                    'sub' => 'distinct logins',                                       'icon' => 'users',    'tone' => 'indigo'],
        ['label' => 'Last 7 days',     'value' => number_format($events7d),                       'sub' => round(100 * ($events7d / max(1,$totalEvents))) . '% of 30d', 'icon' => 'clock',    'tone' => 'emerald'],
        ['label' => 'Peak day',        'value' => $peakDay ? number_format($peakDay->events) : 0, 'sub' => $peakDay ? \Carbon\Carbon::parse($peakDay->day)->format('M j') : '—', 'icon' => 'sparkles', 'tone' => 'sky'],
    ];
    $tones = [
        'indigo'  => ['bg' => 'bg-indigo-50',  'fg' => 'text-indigo-600',  'ring' => 'ring-indigo-100'],
        'sky'     => ['bg' => 'bg-sky-50',     'fg' => 'text-sky-600',     'ring' => 'ring-sky-100'],
        'emerald' => ['bg' => 'bg-emerald-50', 'fg' => 'text-emerald-600', 'ring' => 'ring-emerald-100'],
        'amber'   => ['bg' => 'bg-amber-50',   'fg' => 'text-amber-600',   'ring' => 'ring-amber-100'],
    ];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
    @foreach ($cards as $c)
        @php $t = $tones[$c['tone']]; @endphp
        <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-5 hover:shadow-md transition">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ $c['label'] }}</div>
                    <div class="text-3xl font-semibold text-slate-900 mt-2 tabular-nums tracking-tight">{{ $c['value'] }}</div>
                    <div class="text-xs text-slate-500 mt-1.5">{{ $c['sub'] }}</div>
                </div>
                <div class="w-11 h-11 rounded-xl {{ $t['bg'] }} {{ $t['fg'] }} ring-1 {{ $t['ring'] }} flex items-center justify-center">
                    @include('partials.icon', ['name' => $c['icon'], 'class' => 'w-5 h-5'])
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6 lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-900">Daily activity</h2>
            <span class="text-xs text-slate-500">{{ $daily->count() }} days</span>
        </div>
        <div class="h-64"><canvas id="dailyChart"></canvas></div>
    </div>

    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">By origin</h2>
        <div class="space-y-3">
            @php $maxOrigin = max($byOrigin->pluck('count')->max(), 1); @endphp
            @foreach ($byOrigin as $o)
                @php $pct = round(100 * $o['count'] / $maxOrigin); @endphp
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-medium text-slate-700 capitalize">{{ $o['name'] }}</span>
                        <span class="text-slate-500 tabular-nums">{{ number_format($o['count']) }}</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Activity by hour of day</h2>
        <div class="h-56"><canvas id="hourlyChart"></canvas></div>
    </div>

    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Top components</h2>
        <div class="space-y-2.5">
            @php $maxComp = max($topComponents->pluck('count')->max(), 1); @endphp
            @foreach ($topComponents as $c)
                @php $pct = round(100 * $c['count'] / $maxComp); @endphp
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-medium text-slate-700 font-mono">{{ $c['name'] }}</span>
                        <span class="text-slate-500 tabular-nums">{{ number_format($c['count']) }}</span>
                    </div>
                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-sky-500" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-900">Top events</h2>
            <p class="text-xs text-slate-500 mt-0.5">Most frequent event names</p>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($topEvents as $i => $e)
                <div class="flex items-center gap-3 px-6 py-2.5">
                    <div class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 text-[11px] font-semibold flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-slate-900 truncate">{{ $e['name'] }}</div>
                        <div class="text-xs text-slate-400 truncate font-mono">{{ $e['raw'] }}</div>
                    </div>
                    <div class="text-sm tabular-nums font-semibold text-slate-700">{{ number_format($e['count']) }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-900">Most active users</h2>
            <p class="text-xs text-slate-500 mt-0.5">By event count, last 30 days</p>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($topUsersEnriched as $i => $u)
                <a href="{{ route('users.show', $u['id']) }}" class="flex items-center gap-3 px-6 py-2.5 hover:bg-slate-50 transition">
                    @php $init = strtoupper(substr($u['name'], 0, 2)); @endphp
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-100 to-indigo-200 text-indigo-700 text-xs font-semibold flex items-center justify-center flex-shrink-0">{{ $init }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-slate-900 truncate">{{ $u['name'] }}</div>
                        @if ($u['username'])
                            <div class="text-xs text-slate-500 truncate">@{{ $u['username'] }}</div>
                        @endif
                    </div>
                    <div class="text-sm tabular-nums font-semibold text-slate-700">{{ number_format($u['count']) }}</div>
                </a>
            @endforeach
        </div>
    </div>
</div>

@php
    $dailyLabels = $daily->map(fn ($r) => \Carbon\Carbon::parse($r->day)->format('M j'))->values();
    $dailyEvents = $daily->pluck('events')->values();
    $dailyUsers  = $daily->pluck('users')->values();
    $hourLabels  = $hourlyFilled->pluck('hour')->map(fn ($h) => str_pad($h, 2, '0', STR_PAD_LEFT))->values();
    $hourValues  = $hourlyFilled->pluck('events')->values();
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dailyLabels = {!! $dailyLabels->toJson() !!};
    const dailyEvents = {!! $dailyEvents->toJson() !!};
    const dailyUsers  = {!! $dailyUsers->toJson() !!};

    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: dailyLabels,
            datasets: [
                {
                    label: 'Events',
                    data: dailyEvents,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79,70,229,0.08)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y',
                },
                {
                    label: 'Unique users',
                    data: dailyUsers,
                    borderColor: '#10b981',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [4, 4],
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    tension: 0.3,
                    yAxisID: 'y1',
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, padding: 16, font: { family: 'Inter', size: 12 } } },
                tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 6, titleFont: { family: 'Inter' }, bodyFont: { family: 'Inter' } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 }, color: '#94a3b8', maxRotation: 0 } },
                y: { beginAtZero: true, position: 'left', grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 11 }, color: '#94a3b8' } },
                y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 }, color: '#10b981' } }
            }
        }
    });

    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: {!! $hourLabels->toJson() !!},
            datasets: [{
                data: {!! $hourValues->toJson() !!},
                backgroundColor: '#4f46e5',
                borderRadius: 3,
                hoverBackgroundColor: '#4338ca',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 6, callbacks: { title: (ctx) => ctx[0].label + ':00' } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 10 }, color: '#94a3b8' } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 11 }, color: '#94a3b8' } }
            }
        }
    });
});
</script>
@endsection
