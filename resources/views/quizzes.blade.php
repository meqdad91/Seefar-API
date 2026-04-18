@extends('layouts.app', ['title' => 'Quizzes', 'subtitle' => $totalQuizzes . ' quizzes · ' . number_format($totalAttempts) . ' total attempts'])

@section('content')
@php
    $cards = [
        ['label' => 'Total quizzes',  'value' => number_format($totalQuizzes),                                          'sub' => 'across all courses',                                  'icon' => 'pencil',    'tone' => 'indigo'],
        ['label' => 'Finished',       'value' => number_format($finishedAttempts),                                      'sub' => number_format($inProgress) . ' in progress',           'icon' => 'check',     'tone' => 'emerald'],
        ['label' => 'Pass rate',      'value' => $passRate . '%',                                                       'sub' => number_format($passCount) . ' / ' . number_format($gradedAttempts) . ' graded', 'icon' => 'sparkles',  'tone' => 'amber'],
        ['label' => 'Average score',  'value' => $avgScorePct . '%',                                                    'sub' => 'across finished attempts',                            'icon' => 'chart-bar', 'tone' => 'sky'],
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
            <h2 class="text-sm font-semibold text-slate-900">Attempts over time</h2>
            <span class="text-xs text-slate-500">last 30 days · {{ $daily->sum('attempts') }} attempts</span>
        </div>
        <div class="h-56"><canvas id="attemptsChart"></canvas></div>
    </div>

    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Attempts by state</h2>
        <div class="flex items-center gap-5">
            <div class="relative w-28 h-28 flex-shrink-0">
                <canvas id="stateChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <div class="text-xl font-semibold text-slate-900 tabular-nums leading-none">{{ number_format($totalAttempts) }}</div>
                    <div class="text-[10px] text-slate-500 uppercase mt-0.5">Total</div>
                </div>
            </div>
            <div class="flex-1 space-y-2 text-xs">
                @php
                    $colorByState = ['finished' => 'bg-emerald-500', 'inprogress' => 'bg-amber-500', 'overdue' => 'bg-rose-500', 'abandoned' => 'bg-slate-400'];
                @endphp
                @foreach ($attemptsByState as $state => $count)
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-slate-700 capitalize">
                            <span class="w-2 h-2 rounded-full {{ $colorByState[$state] ?? 'bg-slate-300' }}"></span>
                            {{ $state }}
                        </span>
                        <span class="text-slate-500 tabular-nums">{{ number_format($count) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6 lg:col-span-2">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Score distribution</h2>
        <p class="text-xs text-slate-500 -mt-3 mb-4">{{ number_format($gradedAttempts) }} graded finished attempts</p>
        <div class="h-56"><canvas id="distChart"></canvas></div>
    </div>

    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-900">Most attempted</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($mostList as $i => $q)
                <a href="{{ route('courses.show', $q['course_id']) }}" class="flex items-center gap-3 px-6 py-2.5 hover:bg-slate-50 transition">
                    <div class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 text-[11px] font-semibold flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-slate-900 truncate">{{ $q['name'] }}</div>
                    </div>
                    <div class="text-sm tabular-nums font-semibold text-slate-700">{{ number_format($q['c']) }}</div>
                </a>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-900">Hardest quizzes</h2>
            <p class="text-xs text-slate-500 mt-0.5">Lowest avg score (≥3 attempts)</p>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($hardestList as $q)
                <a href="{{ route('courses.show', $q['course_id']) }}" class="flex items-center gap-3 px-6 py-2.5 hover:bg-slate-50 transition">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-slate-900 truncate">{{ $q['name'] }}</div>
                        <div class="text-xs text-slate-500">{{ $q['attempts'] }} attempts</div>
                    </div>
                    <div class="text-sm tabular-nums font-semibold {{ $q['avg_pct'] < 50 ? 'text-rose-600' : 'text-amber-600' }}">{{ $q['avg_pct'] }}%</div>
                </a>
            @empty
                <div class="px-6 py-8 text-center text-sm text-slate-500">No quizzes with ≥3 attempts.</div>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-900">Easiest quizzes</h2>
            <p class="text-xs text-slate-500 mt-0.5">Highest avg score (≥3 attempts)</p>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($easiestList as $q)
                <a href="{{ route('courses.show', $q['course_id']) }}" class="flex items-center gap-3 px-6 py-2.5 hover:bg-slate-50 transition">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-slate-900 truncate">{{ $q['name'] }}</div>
                        <div class="text-xs text-slate-500">{{ $q['attempts'] }} attempts</div>
                    </div>
                    <div class="text-sm tabular-nums font-semibold text-emerald-600">{{ $q['avg_pct'] }}%</div>
                </a>
            @empty
                <div class="px-6 py-8 text-center text-sm text-slate-500">No quizzes with ≥3 attempts.</div>
            @endforelse
        </div>
    </div>
</div>

@php
    $dailyLabels = $daily->map(fn ($r) => \Carbon\Carbon::parse($r->day)->format('M j'))->values();
    $dailyValues = $daily->pluck('attempts')->values();
    $bucketLabels = collect(array_keys($buckets));
    $bucketValues = collect(array_values($buckets));
    $stateLabels = $attemptsByState->keys();
    $stateValues = $attemptsByState->values();
    $stateColors = ['finished' => '#10b981', 'inprogress' => '#f59e0b', 'overdue' => '#f43f5e', 'abandoned' => '#94a3b8'];
    $stateBg = $stateLabels->map(fn ($s) => $stateColors[$s] ?? '#cbd5e1');
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('attemptsChart'), {
        type: 'line',
        data: {
            labels: {!! $dailyLabels->toJson() !!},
            datasets: [{
                label: 'Attempts',
                data: {!! $dailyValues->toJson() !!},
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79,70,229,0.08)',
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 4,
                tension: 0.3,
                fill: true,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 6 } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 }, color: '#94a3b8', maxRotation: 0 } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 11 }, color: '#94a3b8' } }
            }
        }
    });

    new Chart(document.getElementById('stateChart'), {
        type: 'doughnut',
        data: { labels: {!! $stateLabels->toJson() !!}, datasets: [{ data: {!! $stateValues->toJson() !!}, backgroundColor: {!! $stateBg->toJson() !!}, borderWidth: 0 }] },
        options: {
            responsive: true, maintainAspectRatio: false,
            cutout: '72%',
            plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 6 } }
        }
    });

    new Chart(document.getElementById('distChart'), {
        type: 'bar',
        data: {
            labels: {!! $bucketLabels->toJson() !!},
            datasets: [{
                data: {!! $bucketValues->toJson() !!},
                backgroundColor: ['#f43f5e', '#fb923c', '#facc15', '#84cc16', '#10b981'],
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 6 } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 }, color: '#475569' } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 11 }, color: '#94a3b8' } }
            }
        }
    });
});
</script>
@endsection
