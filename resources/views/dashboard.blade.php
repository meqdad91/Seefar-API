@extends('layouts.app', ['title' => 'Dashboard', 'subtitle' => 'Overview of platform activity'])

@section('content')

@include('partials.filters')

@php
    $cards = [
        [
            'label' => 'Total users',
            'value' => number_format($stats['users_total']),
            'sub' => number_format($stats['users_active_30d']) . ' active in 30d',
            'icon' => 'users',
            'tone' => 'indigo',
        ],
        [
            'label' => 'Visible courses',
            'value' => number_format($stats['courses_visible']),
            'sub' => number_format($stats['courses_total']) . ' total',
            'icon' => 'book',
            'tone' => 'sky',
        ],
        [
            'label' => 'Enrolments',
            'value' => number_format($stats['enrolments']),
            'sub' => number_format($stats['completions_done']) . ' completions',
            'icon' => 'check',
            'tone' => 'emerald',
        ],
        [
            'label' => 'Quiz attempts',
            'value' => number_format($stats['quiz_attempts']),
            'sub' => number_format($stats['quizzes_total']) . ' quizzes | ' . number_format($stats['quiz_attempts_finished']) . ' finished',
            'icon' => 'pencil',
            'tone' => 'amber',
        ],
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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-900">User activity</h2>
            <span class="text-xs text-slate-500">based on last access</span>
        </div>
        <div class="flex items-center gap-6">
            <div class="relative w-40 h-40 flex-shrink-0">
                <canvas id="activityChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <div class="text-2xl font-semibold text-slate-900 tabular-nums leading-none">{{ number_format($stats['users_active_30d']) }}</div>
                    <div class="text-[11px] text-slate-500 uppercase tracking-wider mt-1">Active 30d</div>
                </div>
            </div>
            <div class="flex-1 space-y-3">
                @php
                    $rows = [
                        ['Active 7d',     $stats['users_active_7d'],                                     'bg-indigo-600'],
                        ['Active 8–30d',  $stats['users_active_30d'] - $stats['users_active_7d'],        'bg-indigo-300'],
                        ['Inactive 30d+', $stats['users_total'] - $stats['users_active_30d'],            'bg-slate-200'],
                    ];
                @endphp
                @foreach ($rows as [$label, $value, $color])
                    @php $pct = $stats['users_total'] > 0 ? round(100 * $value / $stats['users_total'], 1) : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="flex items-center gap-2 text-slate-600">
                                <span class="w-2 h-2 rounded-full {{ $color }}"></span>
                                {{ $label }}
                            </span>
                            <span class="text-slate-500 tabular-nums">{{ number_format($value) }} <span class="text-slate-400">({{ $pct }}%)</span></span>
                        </div>
                        <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full {{ $color }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Course completion</h2>
        @php $completionRate = $stats['completions_started'] > 0 ? round(100 * $stats['completions_done'] / $stats['completions_started']) : 0; @endphp
        <div class="text-center py-3">
            <div class="relative inline-flex items-center justify-center w-32 h-32">
                <svg class="absolute inset-0 -rotate-90 w-32 h-32" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="url(#completionGrad)" stroke-width="3" stroke-linecap="round"
                            stroke-dasharray="{{ $completionRate * 0.974 }} 100"/>
                    <defs><linearGradient id="completionGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#10b981"/><stop offset="100%" stop-color="#059669"/></linearGradient></defs>
                </svg>
                <div class="text-center">
                    <div class="text-3xl font-semibold text-slate-900 tabular-nums">{{ $completionRate }}<span class="text-base">%</span></div>
                </div>
            </div>
            <div class="text-xs text-slate-500 mt-3">
                <span class="font-semibold text-slate-700">{{ number_format($stats['completions_done']) }}</span>
                of {{ number_format($stats['completions_started']) }} learners
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Top courses</h2>
                <p class="text-xs text-slate-500 mt-0.5">By enrolment count</p>
            </div>
            <a href="{{ route('courses.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View all →</a>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($topCourses as $i => $c)
                <a href="{{ route('courses.show', $c->id) }}" class="flex items-center gap-4 px-6 py-3 hover:bg-slate-50 transition">
                    <div class="w-7 h-7 rounded-md bg-gradient-to-br from-indigo-500 to-indigo-700 text-white text-xs font-semibold flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-slate-900 truncate">{{ $c->fullname }}</div>
                    </div>
                    <div class="text-sm tabular-nums text-slate-700 font-semibold">{{ number_format($c->enrolments) }}</div>
                </a>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Recent logins</h2>
                <p class="text-xs text-slate-500 mt-0.5">Most recent admin/user logins</p>
            </div>
            <a href="{{ route('users.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View all →</a>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($recentLogins as $u)
                <a href="{{ route('users.show', $u->id) }}" class="flex items-center gap-3 px-6 py-3 hover:bg-slate-50 transition">
                    @php
                        $name = trim($u->firstname.' '.$u->lastname) ?: $u->username;
                        $initials = strtoupper(substr($u->firstname,0,1).substr($u->lastname,0,1)) ?: strtoupper(substr($u->username,0,2));
                    @endphp
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-slate-200 to-slate-300 text-slate-700 text-xs font-semibold flex items-center justify-center flex-shrink-0">{{ $initials }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-slate-900 truncate">{{ $name }}</div>
                        <div class="text-xs text-slate-500 truncate">{{ $u->email }}</div>
                    </div>
                    <div class="text-xs text-slate-400 whitespace-nowrap">{{ \Carbon\Carbon::createFromTimestamp($u->lastlogin)->diffForHumans() }}</div>
                </a>
            @endforeach
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const activeOnly7   = {{ $stats['users_active_7d'] }};
    const active8to30   = {{ $stats['users_active_30d'] - $stats['users_active_7d'] }};
    const inactive30    = {{ $stats['users_total'] - $stats['users_active_30d'] }};

    new Chart(document.getElementById('activityChart'), {
        type: 'doughnut',
        data: {
            labels: ['Active 7d', 'Active 8–30d', 'Inactive 30d+'],
            datasets: [{
                data: [activeOnly7, active8to30, inactive30],
                backgroundColor: ['#4f46e5', '#a5b4fc', '#e2e8f0'],
                borderWidth: 0,
                hoverOffset: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 6, titleFont: { family: 'Inter' }, bodyFont: { family: 'Inter' } }
            }
        }
    });
});
</script>
@endsection
