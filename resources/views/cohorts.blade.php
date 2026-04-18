@extends('layouts.app', ['title' => 'Cohorts', 'subtitle' => $totalCohorts . ' cohorts · ' . number_format($totalMembers) . ' members total'])

@section('content')
@php
    $cards = [
        ['label' => 'Cohorts',          'value' => number_format($totalCohorts),  'sub' => 'visible',                                                                       'icon' => 'users',     'tone' => 'indigo'],
        ['label' => 'Members',          'value' => number_format($totalMembers),  'sub' => 'across all cohorts',                                                            'icon' => 'users',     'tone' => 'sky'],
        ['label' => 'Active 30d',       'value' => number_format($totalActive),   'sub' => $totalMembers > 0 ? round(100 * $totalActive / $totalMembers) . '% of members' : '—', 'icon' => 'fire',      'tone' => 'emerald'],
        ['label' => 'Avg cohort size',  'value' => number_format($avgCohortSize, 1), 'sub' => 'members per cohort',                                                         'icon' => 'chart-bar', 'tone' => 'amber'],
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
        <h2 class="text-sm font-semibold text-slate-900 mb-1">Members per cohort</h2>
        <p class="text-xs text-slate-500 mb-4">Total members & active members (30d)</p>
        <div class="h-72"><canvas id="membersChart"></canvas></div>
    </div>
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-1">Performance comparison</h2>
        <p class="text-xs text-slate-500 mb-4">Average grade & completion rate</p>
        <div class="h-72"><canvas id="performanceChart"></canvas></div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
    @foreach ($cohorts as $c)
        @php
            $hasMembers = $c['members'] > 0;
        @endphp
        <div class="bg-white rounded-xl shadow-card border border-slate-200/70 overflow-hidden hover:shadow-md transition">
            <div class="px-5 pt-5 pb-3 border-b border-slate-100">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-700 text-white flex items-center justify-center flex-shrink-0">
                        @include('partials.icon', ['name' => 'users', 'class' => 'w-5 h-5'])
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-slate-900 truncate">{{ $c['name'] }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5 tabular-nums">
                            {{ number_format($c['members']) }} members
                            @if ($c['suspended'] > 0)
                                · <span class="text-rose-600">{{ $c['suspended'] }} suspended</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            @if (! $hasMembers)
                <div class="p-6 text-center text-sm text-slate-400">No members yet.</div>
            @else
                <div class="p-5 space-y-4">
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="text-slate-600 font-medium">Active 30d</span>
                            <span class="text-slate-700 tabular-nums">{{ $c['active'] }} <span class="text-slate-400">({{ $c['active_pct'] }}%)</span></span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-600" style="width: {{ $c['active_pct'] }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="text-slate-600 font-medium">Completion rate</span>
                            <span class="text-slate-700 tabular-nums">
                                @if ($c['completion_rate'] !== null)
                                    {{ $c['completions_done'] }}/{{ $c['completions_started'] }} <span class="text-slate-400">({{ $c['completion_rate'] }}%)</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600" style="width: {{ $c['completion_rate'] ?? 0 }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="text-slate-600 font-medium">Avg grade</span>
                            <span class="tabular-nums font-semibold
                                {{ $c['avg_grade'] === null ? 'text-slate-400' : ($c['avg_grade'] >= 70 ? 'text-emerald-600' : ($c['avg_grade'] >= 50 ? 'text-amber-600' : 'text-rose-600')) }}">
                                @if ($c['avg_grade'] !== null)
                                    {{ $c['avg_grade'] }}%
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                        @if ($c['avg_grade'] !== null)
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                @php
                                    $gradeColor = $c['avg_grade'] >= 70 ? 'from-emerald-500 to-emerald-600' : ($c['avg_grade'] >= 50 ? 'from-amber-500 to-amber-600' : 'from-rose-500 to-rose-600');
                                @endphp
                                <div class="h-full bg-gradient-to-r {{ $gradeColor }}" style="width: {{ $c['avg_grade'] }}%"></div>
                            </div>
                        @endif
                        <div class="text-[11px] text-slate-400 mt-1">{{ $c['graded_users'] }} {{ Str::plural('graded user', $c['graded_users']) }}</div>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Quiz attempts</span>
                        <span class="text-slate-700 tabular-nums">
                            <span class="font-semibold">{{ number_format($c['quiz_attempts']) }}</span>
                            <span class="text-slate-400">({{ $c['attempts_per_member'] }}/member)</span>
                        </span>
                    </div>
                </div>
            @endif

            <div class="px-5 py-3 bg-slate-50/50 border-t border-slate-100 text-right">
                <a href="/api/cohorts/{{ $c['id'] }}/members" target="_blank"
                   class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View members (API) →</a>
            </div>
        </div>
    @endforeach
</div>

@php
    $labels = $cohorts->pluck('name')->values();
    $memberData = $cohorts->pluck('members')->values();
    $activeData = $cohorts->pluck('active')->values();
    $gradeData = $cohorts->map(fn ($c) => $c['avg_grade'] ?? 0)->values();
    $completionData = $cohorts->map(fn ($c) => $c['completion_rate'] ?? 0)->values();
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = {!! $labels->toJson() !!};

    new Chart(document.getElementById('membersChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Members',  data: {!! $memberData->toJson() !!}, backgroundColor: '#a5b4fc', borderRadius: 4 },
                { label: 'Active 30d', data: {!! $activeData->toJson() !!}, backgroundColor: '#4f46e5', borderRadius: 4 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, padding: 16, font: { family: 'Inter', size: 11 } } },
                tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 6 }
            },
            scales: {
                x: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 11 }, color: '#94a3b8' } },
                y: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 }, color: '#475569' } }
            }
        }
    });

    new Chart(document.getElementById('performanceChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Avg grade %',     data: {!! $gradeData->toJson() !!},     backgroundColor: '#10b981', borderRadius: 4 },
                { label: 'Completion %',    data: {!! $completionData->toJson() !!}, backgroundColor: '#6366f1', borderRadius: 4 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, padding: 16, font: { family: 'Inter', size: 11 } } },
                tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 6, callbacks: { label: (ctx) => ctx.dataset.label + ': ' + ctx.parsed.x + '%' } }
            },
            scales: {
                x: { beginAtZero: true, max: 100, grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 11 }, color: '#94a3b8', callback: (v) => v + '%' } },
                y: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 }, color: '#475569' } }
            }
        }
    });
});
</script>
@endsection
