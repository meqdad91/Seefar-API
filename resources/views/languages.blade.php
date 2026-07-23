@extends('layouts.app', ['title' => 'Languages & Programs', 'subtitle' => $totalCategories . ' top-level categories · ' . number_format($totalCourses) . ' courses · ' . number_format($totalEnrolled) . ' learners'])

@section('content')

@include('partials.filters')

@php
    $cards = [
        ['label' => 'Programs',         'value' => number_format($totalCategories), 'sub' => 'top-level categories',                         'icon' => 'globe',     'tone' => 'indigo'],
        ['label' => 'Total courses',    'value' => number_format($totalCourses),    'sub' => 'across all programs',                          'icon' => 'book',      'tone' => 'sky'],
        ['label' => 'Learners',         'value' => number_format($totalEnrolled),   'sub' => 'distinct enrolled users',                       'icon' => 'users',     'tone' => 'amber'],
        ['label' => 'Completion rate',  'value' => $overallCompletionRate . '%',    'sub' => number_format($totalCompleted) . ' completed',  'icon' => 'check',     'tone' => 'emerald'],
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
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-1">Enrolment by program</h2>
        <p class="text-xs text-slate-500 mb-4">Total enrolled vs active 30d</p>
        <div class="h-64"><canvas id="enrolChart"></canvas></div>
    </div>
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-1">Completion rate % by program</h2>
        <p class="text-xs text-slate-500 mb-4">% of enrolled learners completing requirements</p>
        <div class="h-64"><canvas id="completionChart"></canvas></div>
    </div>
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-1">Average grade % by program</h2>
        <p class="text-xs text-slate-500 mb-4">Scores among graded submission subset</p>
        <div class="h-64"><canvas id="gradeChart"></canvas></div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
    @foreach ($languages as $l)
        @php $hasData = $l['courses'] > 0; @endphp
        <div class="bg-white rounded-xl shadow-card border border-slate-200/70 overflow-hidden hover:shadow-md transition">
            <div class="px-5 pt-5 pb-3 border-b border-slate-100">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-sky-500 to-indigo-600 text-white flex items-center justify-center flex-shrink-0">
                        @include('partials.icon', ['name' => 'globe', 'class' => 'w-5 h-5'])
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-slate-900 truncate">{{ $l['short'] }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $l['name'] }}</p>
                    </div>
                </div>
            </div>

            @if (! $hasData)
                <div class="p-6 text-center text-sm text-slate-400">No courses in this program.</div>
            @else
                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div>
                            <div class="text-xl font-semibold text-slate-900 tabular-nums">{{ $l['courses'] }}</div>
                            <div class="text-[11px] text-slate-500 uppercase tracking-wider mt-0.5">Courses</div>
                        </div>
                        <div>
                            <div class="text-xl font-semibold text-slate-900 tabular-nums">{{ number_format($l['enrolled']) }}</div>
                            <div class="text-[11px] text-slate-500 uppercase tracking-wider mt-0.5">Learners</div>
                        </div>
                        <div>
                            <div class="text-xl font-semibold text-slate-900 tabular-nums">{{ number_format($l['quizzes']) }}</div>
                            <div class="text-[11px] text-slate-500 uppercase tracking-wider mt-0.5">Quizzes</div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="text-slate-600 font-medium">Active 30d</span>
                            <span class="text-slate-700 tabular-nums">{{ $l['active_30d'] }} <span class="text-slate-400">({{ $l['active_pct'] }}%)</span></span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-600" style="width: {{ $l['active_pct'] }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="text-slate-600 font-medium">Completion</span>
                            <span class="text-slate-700 tabular-nums">
                                @if ($l['completion_rate'] !== null)
                                    {{ $l['completions_done'] }}/{{ $l['completions_started'] }} <span class="text-slate-400">({{ $l['completion_rate'] }}%)</span>
                                @else
                                    <span class="text-slate-400">no data</span>
                                @endif
                            </span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600" style="width: {{ $l['completion_rate'] ?? 0 }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="text-slate-600 font-medium">Avg grade</span>
                            <span class="tabular-nums font-semibold
                                {{ $l['avg_grade'] === null ? 'text-slate-400' : ($l['avg_grade'] >= 70 ? 'text-emerald-600' : ($l['avg_grade'] >= 50 ? 'text-amber-600' : 'text-rose-600')) }}">
                                {{ $l['avg_grade'] !== null ? $l['avg_grade'] . '%' : '—' }}
                            </span>
                        </div>
                        @if ($l['avg_grade'] !== null)
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                @php
                                    $gradeColor = $l['avg_grade'] >= 70 ? 'from-emerald-500 to-emerald-600' : ($l['avg_grade'] >= 50 ? 'from-amber-500 to-amber-600' : 'from-rose-500 to-rose-600');
                                @endphp
                                <div class="h-full bg-gradient-to-r {{ $gradeColor }}" style="width: {{ $l['avg_grade'] }}%"></div>
                            </div>
                        @endif
                        <div class="text-[11px] text-slate-400 mt-1">{{ number_format($l['graded_count']) }} {{ Str::plural('graded user', $l['graded_count']) }}</div>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Quiz attempts</span>
                        <span class="text-slate-700 tabular-nums font-semibold">{{ number_format($l['quiz_attempts']) }}</span>
                    </div>
                </div>
            @endif

            <div class="px-5 py-3 bg-slate-50/50 border-t border-slate-100 text-right">
                <a href="{{ route('courses.index', ['search' => $l['short']]) }}"
                   class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View courses →</a>
            </div>
        </div>
    @endforeach
</div>

@php
    $labels = $languages->pluck('short')->values();
    $enrolledData = $languages->pluck('enrolled')->values();
    $activeData = $languages->pluck('active_30d')->values();
    $gradeData = $languages->map(fn ($l) => $l['avg_grade'] ?? 0)->values();
    $completionData = $languages->map(fn ($l) => $l['completion_rate'] ?? 0)->values();
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = {!! $labels->toJson() !!};

    new Chart(document.getElementById('enrolChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Enrolled',  data: {!! $enrolledData->toJson() !!}, backgroundColor: '#a5b4fc', borderRadius: 4 },
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

    new Chart(document.getElementById('completionChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Completion %', data: {!! $completionData->toJson() !!}, backgroundColor: '#6366f1', borderRadius: 4 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 6, callbacks: { label: (ctx) => 'Completion: ' + ctx.parsed.x + '%' } }
            },
            scales: {
                x: { beginAtZero: true, max: 100, grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 11 }, color: '#94a3b8', callback: (v) => v + '%' } },
                y: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 }, color: '#475569' } }
            }
        }
    });

    new Chart(document.getElementById('gradeChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Avg grade %', data: {!! $gradeData->toJson() !!}, backgroundColor: '#10b981', borderRadius: 4 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 6, callbacks: { label: (ctx) => 'Avg Grade: ' + ctx.parsed.x + '%' } }
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
