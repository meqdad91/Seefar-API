@extends('layouts.app', ['title' => 'At-risk learners', 'subtitle' => $counts['at_risk'] . ' of ' . $counts['total_enrolled'] . ' enrolled users flagged'])

@section('content')

@include('partials.filters')

@php
    $cards = [
        ['label' => 'Inactive 60d+', 'value' => number_format($counts['inactive']),  'sub' => 'no recent access',         'icon' => 'clock',     'tone' => 'rose',    'filter' => 'inactive'],
        ['label' => 'Low grades',    'value' => number_format($counts['low_grade']), 'sub' => 'avg below 50%',            'icon' => 'chart-bar', 'tone' => 'amber',   'filter' => 'low_grade'],
        ['label' => 'Stalled',       'value' => number_format($counts['stalled']),   'sub' => 'started, not completed',   'icon' => 'fire',      'tone' => 'orange',  'filter' => 'stalled'],
        ['label' => 'No quiz',       'value' => number_format($counts['no_quiz']),   'sub' => 'enrolled, no attempts',    'icon' => 'pencil',    'tone' => 'sky',     'filter' => 'no_quiz'],
    ];
    $tones = [
        'rose'    => ['bg' => 'bg-rose-50',    'fg' => 'text-rose-600',    'ring' => 'ring-rose-100',    'pill' => 'bg-rose-100 text-rose-700'],
        'amber'   => ['bg' => 'bg-amber-50',   'fg' => 'text-amber-600',   'ring' => 'ring-amber-100',   'pill' => 'bg-amber-100 text-amber-700'],
        'orange'  => ['bg' => 'bg-orange-50',  'fg' => 'text-orange-600',  'ring' => 'ring-orange-100',  'pill' => 'bg-orange-100 text-orange-700'],
        'sky'     => ['bg' => 'bg-sky-50',     'fg' => 'text-sky-600',     'ring' => 'ring-sky-100',     'pill' => 'bg-sky-100 text-sky-700'],
        'slate'   => ['bg' => 'bg-slate-50',   'fg' => 'text-slate-600',   'ring' => 'ring-slate-200',   'pill' => 'bg-slate-100 text-slate-700'],
    ];
    $flagMeta = [
        'inactive'  => ['label' => 'Inactive',   'tone' => 'rose'],
        'low_grade' => ['label' => 'Low grade',  'tone' => 'amber'],
        'stalled'   => ['label' => 'Stalled',    'tone' => 'orange'],
        'no_quiz'   => ['label' => 'No quiz',    'tone' => 'sky'],
    ];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
    @foreach ($cards as $c)
        @php $t = $tones[$c['tone']]; $isActive = $filter === $c['filter']; @endphp
        <a href="{{ route('atrisk', ['filter' => $isActive ? 'all' : $c['filter']]) }}"
           class="bg-white rounded-xl shadow-card border p-5 transition hover:shadow-md
                  {{ $isActive ? 'border-' . $c['tone'] . '-300 ring-2 ring-' . $c['tone'] . '-100' : 'border-slate-200/70' }}">
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
        </a>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
        <div class="flex items-center gap-2 mb-2">
            <h2 class="text-sm font-semibold text-slate-900">Risk overview</h2>
            <div class="relative group cursor-pointer">
                <span class="w-4 h-4 rounded-full bg-slate-100 text-slate-400 hover:bg-rose-50 hover:text-rose-600 flex items-center justify-center text-[10px] font-bold border border-slate-200">i</span>
                <div class="absolute left-0 bottom-full mb-2 hidden group-hover:block w-72 p-3 bg-slate-900 text-white text-xs rounded-lg shadow-xl z-20 pointer-events-none">
                    <strong>What does this gauge show?</strong><br>
                    It represents the percentage of total enrolled learners ({{ number_format($counts['total_enrolled']) }}) who triggered 1 or more risk indicators:
                    <ul class="list-disc pl-4 mt-1 space-y-0.5 text-[11px] text-slate-300">
                        <li><strong>Inactive:</strong> No access for 60+ days</li>
                        <li><strong>Low grade:</strong> Avg grade below 50%</li>
                        <li><strong>Stalled:</strong> Enrolled 60+ days without finishing</li>
                        <li><strong>No quiz:</strong> 0 quiz attempts submitted</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="text-center py-2">
            @php $atRiskPct = $counts['total_enrolled'] > 0 ? round(100 * $counts['at_risk'] / $counts['total_enrolled']) : 0; @endphp
            <div class="relative inline-flex items-center justify-center w-32 h-32">
                <svg class="absolute inset-0 -rotate-90 w-32 h-32" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="url(#riskGrad)" stroke-width="3" stroke-linecap="round" stroke-dasharray="{{ $atRiskPct * 0.974 }} 100"/>
                    <defs><linearGradient id="riskGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#f43f5e"/><stop offset="100%" stop-color="#e11d48"/></linearGradient></defs>
                </svg>
                <div class="text-3xl font-semibold text-slate-900 tabular-nums">{{ $atRiskPct }}<span class="text-base">%</span></div>
            </div>
            <div class="text-xs text-slate-500 mt-3">
                <span class="font-semibold text-rose-600">{{ number_format($counts['at_risk']) }}</span>
                of <span class="font-semibold text-slate-700">{{ number_format($counts['total_enrolled']) }}</span> enrolled learners flagged
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6 lg:col-span-2">
        <div class="flex items-center gap-2 mb-4">
            <h2 class="text-sm font-semibold text-slate-900">Last access distribution</h2>
            <div class="relative group cursor-pointer">
                <span class="w-4 h-4 rounded-full bg-slate-100 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 flex items-center justify-center text-[10px] font-bold border border-slate-200">i</span>
                <div class="absolute right-0 bottom-full mb-2 hidden group-hover:block w-72 p-2.5 bg-slate-900 text-white text-xs rounded-lg shadow-xl z-20 pointer-events-none">
                    Categorizes all enrolled learners by their last platform access recency (Never, within 7 days, 8–30 days, 31–90 days, or 90+ days inactive).
                </div>
            </div>
        </div>
        <div class="h-48"><canvas id="accessChart"></canvas></div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-card border border-slate-200/70 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Flagged learners</h2>
            <p class="text-xs text-slate-500 mt-0.5">{{ $list->count() }} shown · sorted by risk + inactivity</p>
        </div>
        <div class="flex items-center gap-1.5 text-xs">
            @foreach (['all' => 'All'] + collect($flagMeta)->mapWithKeys(fn ($v, $k) => [$k => $v['label']])->toArray() as $k => $v)
                @php $active = $filter === $k; @endphp
                <a href="{{ route('atrisk', ['filter' => $k]) }}"
                   class="px-2.5 py-1 rounded-md font-medium transition
                          {{ $active ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    {{ $v }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-xs text-slate-500 uppercase tracking-wider border-b border-slate-100 bg-slate-50/50">
                <tr>
                    <th class="text-left px-6 py-3 font-medium">User</th>
                    <th class="text-left px-6 py-3 font-medium">Risk flags</th>
                    <th class="text-right px-6 py-3 font-medium">Last access</th>
                    <th class="text-right px-6 py-3 font-medium">Avg grade</th>
                    <th class="text-right px-6 py-3 font-medium">Courses</th>
                    <th class="text-right px-6 py-3 font-medium">Quiz attempts</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($list as $u)
                    @php $init = strtoupper(substr($u->name, 0, 2)); @endphp
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-rose-100 to-rose-200 text-rose-700 text-xs font-semibold flex items-center justify-center flex-shrink-0">{{ $init }}</div>
                                <div class="min-w-0">
                                    <div class="font-medium text-slate-900 truncate">{{ $u->name }}</div>
                                    <div class="text-xs text-slate-500 truncate">{{ $u->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($u->flags as $f)
                                    @php $m = $flagMeta[$f]; @endphp
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium {{ $tones[$m['tone']]['pill'] }}">{{ $m['label'] }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-3 text-right text-xs text-slate-500 whitespace-nowrap">
                            @if ($u->lastaccess)
                                {{ \Carbon\Carbon::createFromTimestamp($u->lastaccess)->diffForHumans() }}
                            @else
                                <span class="text-rose-500 font-medium">Never</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right tabular-nums">
                            @if ($u->avg_grade_pct !== null)
                                <span class="font-semibold {{ $u->avg_grade_pct < 50 ? 'text-rose-600' : ($u->avg_grade_pct < 70 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $u->avg_grade_pct }}%</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right tabular-nums text-slate-700">{{ $u->enrolled_courses }}</td>
                        <td class="px-6 py-3 text-right tabular-nums text-slate-700">{{ $u->quiz_attempts }}</td>
                        <td class="px-6 py-3 text-right">
                            <a href="{{ route('users.show', $u->id) }}"
                               class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                                View
                                @include('partials.icon', ['name' => 'arrow-right', 'class' => 'w-3.5 h-3.5'])
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">No learners match this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@php
    $accessLabels = collect(array_keys($accessBuckets));
    $accessValues = collect(array_values($accessBuckets));
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('accessChart'), {
        type: 'bar',
        data: {
            labels: {!! $accessLabels->toJson() !!},
            datasets: [{
                data: {!! $accessValues->toJson() !!},
                backgroundColor: ['#94a3b8', '#10b981', '#84cc16', '#f59e0b', '#f43f5e'],
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
