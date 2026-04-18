@extends('layouts.app', ['title' => $course->fullname, 'subtitle' => $course->categoryInfo->name ?? null])

@section('content')
<a href="{{ route('courses.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 mb-4">
    @include('partials.icon', ['name' => 'arrow-left', 'class' => 'w-4 h-4'])
    Back to courses
</a>

<div class="bg-gradient-to-br from-sky-600 to-indigo-700 rounded-xl p-6 text-white shadow-lg shadow-indigo-600/20 mb-6">
    <div class="flex items-start gap-5">
        <div class="w-14 h-14 rounded-xl bg-white/15 backdrop-blur ring-2 ring-white/20 flex items-center justify-center flex-shrink-0">
            @include('partials.icon', ['name' => 'book', 'class' => 'w-7 h-7 text-white'])
        </div>
        <div class="flex-1 min-w-0">
            <h2 class="text-xl font-semibold">{{ $course->fullname }}</h2>
            <div class="text-sm text-sky-100 mt-1">{{ $course->shortname }} · #{{ $course->id }}</div>
            <div class="flex gap-4 mt-3 text-sm text-sky-100">
                @if ($course->categoryInfo)
                    <span class="inline-flex items-center gap-1.5">
                        @include('partials.icon', ['name' => 'building', 'class' => 'w-4 h-4'])
                        {{ $course->categoryInfo->name }}
                    </span>
                @endif
                <span class="inline-flex items-center gap-1.5">
                    @include('partials.icon', ['name' => 'globe', 'class' => 'w-4 h-4'])
                    {{ $course->lang ?: 'default' }}
                </span>
            </div>
        </div>
    </div>
</div>

@php
    $cards = [
        ['label' => 'Students',    'value' => number_format($studentCount), 'icon' => 'users',     'tone' => 'indigo'],
        ['label' => 'Activities',  'value' => number_format($activityCount),'icon' => 'pencil',    'tone' => 'sky'],
        ['label' => 'Quizzes',     'value' => number_format($quizCount),    'icon' => 'chart-bar', 'tone' => 'amber'],
        ['label' => 'Avg. grade',  'value' => $avgGrade !== null ? number_format($avgGrade, 1) : '—', 'icon' => 'sparkles', 'tone' => 'emerald'],
    ];
    $tones = [
        'indigo'  => ['bg' => 'bg-indigo-50',  'fg' => 'text-indigo-600',  'ring' => 'ring-indigo-100'],
        'sky'     => ['bg' => 'bg-sky-50',     'fg' => 'text-sky-600',     'ring' => 'ring-sky-100'],
        'emerald' => ['bg' => 'bg-emerald-50', 'fg' => 'text-emerald-600', 'ring' => 'ring-emerald-100'],
        'amber'   => ['bg' => 'bg-amber-50',   'fg' => 'text-amber-600',   'ring' => 'ring-amber-100'],
    ];
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach ($cards as $c)
        @php $t = $tones[$c['tone']]; @endphp
        <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg {{ $t['bg'] }} {{ $t['fg'] }} ring-1 {{ $t['ring'] }} flex items-center justify-center">
                    @include('partials.icon', ['name' => $c['icon'], 'class' => 'w-5 h-5'])
                </div>
                <div>
                    <div class="text-xs text-slate-500 uppercase tracking-wider">{{ $c['label'] }}</div>
                    <div class="text-xl font-semibold text-slate-900 tabular-nums">{{ $c['value'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Completion</h2>
        @php $rate = $completionsStarted > 0 ? round(100 * $completionsDone / $completionsStarted) : 0; @endphp
        <div class="text-center py-3">
            <div class="relative inline-flex items-center justify-center w-32 h-32">
                <svg class="absolute inset-0 -rotate-90 w-32 h-32" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="url(#cgrad)" stroke-width="3" stroke-linecap="round" stroke-dasharray="{{ $rate * 0.974 }} 100"/>
                    <defs><linearGradient id="cgrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#10b981"/><stop offset="100%" stop-color="#059669"/></linearGradient></defs>
                </svg>
                <div class="text-3xl font-semibold text-slate-900 tabular-nums">{{ $rate }}<span class="text-base">%</span></div>
            </div>
            <div class="text-xs text-slate-500 mt-3">
                <span class="font-semibold text-slate-700">{{ number_format($completionsDone) }}</span>
                of {{ number_format($completionsStarted) }} learners
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Recent students</h2>
        @if ($students->isEmpty())
            <p class="text-sm text-slate-500 text-center py-6">No enrolled students.</p>
        @else
            <ul class="space-y-2">
                @foreach ($students as $s)
                    @php
                        $sName = trim($s->firstname.' '.$s->lastname) ?: $s->username;
                        $sInit = strtoupper(substr($s->firstname,0,1).substr($s->lastname,0,1)) ?: strtoupper(substr($s->username,0,2));
                    @endphp
                    <li>
                        <a href="{{ route('users.show', $s->id) }}" class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-slate-50 transition">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-100 to-indigo-200 text-indigo-700 text-xs font-semibold flex items-center justify-center flex-shrink-0">{{ $sInit }}</div>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-slate-900 truncate">{{ $sName }}</div>
                                <div class="text-xs text-slate-500 truncate">{{ $s->email }}</div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Activities</h2>
        @if ($activities->isEmpty())
            <p class="text-sm text-slate-500 text-center py-6">No activities.</p>
        @else
            <ul class="text-sm space-y-1.5">
                @foreach ($activities as $a)
                    <li class="flex items-center justify-between px-2 py-1.5 border-b border-slate-100 last:border-0">
                        <span class="capitalize text-slate-800">{{ $a->type }}</span>
                        <span class="text-xs text-slate-400">section {{ $a->section }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

@if ($course->summary)
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6 mt-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Summary</h2>
        <div class="text-sm text-slate-700 leading-relaxed prose-sm">{!! $course->summary !!}</div>
    </div>
@endif
@endsection
