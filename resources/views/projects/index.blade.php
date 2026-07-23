@extends('layouts.app', ['title' => 'Projects', 'subtitle' => 'Overview of projects and course groupings'])

@section('content')

@include('partials.filters')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">All Projects</h2>
        <p class="text-xs text-slate-500">Each project groups related courses and consolidates learner analytics</p>
    </div>
    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-full border border-indigo-100">
        {{ count($projects) }} Project(s)
    </span>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    @forelse ($projects as $p)
        <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6 flex flex-col justify-between hover:shadow-md transition">
            <div>
                <div class="flex items-start justify-between gap-3 mb-3">
                    <h3 class="text-base font-semibold text-slate-900 group-hover:text-indigo-600 transition">
                        <a href="{{ route('projects.show', $p->id) }}">{{ $p->name }}</a>
                    </h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 border border-brand-200/60 whitespace-nowrap">
                        {{ $p->course_count }} {{ Str::plural('Course', $p->course_count) }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mb-5 line-clamp-2">{{ $p->description }}</p>

                <div class="grid grid-cols-3 gap-2 py-3 px-3 bg-slate-50 rounded-lg border border-slate-100 text-center mb-5">
                    <div>
                        <div class="text-[11px] text-slate-400 uppercase font-medium">Learners</div>
                        <div class="text-sm font-semibold text-slate-800 tabular-nums mt-0.5">{{ number_format($p->enrolments) }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-slate-400 uppercase font-medium">Completions</div>
                        <div class="text-sm font-semibold text-emerald-600 tabular-nums mt-0.5">{{ number_format($p->completions) }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-slate-400 uppercase font-medium">Quizzes</div>
                        <div class="text-sm font-semibold text-amber-600 tabular-nums mt-0.5">{{ number_format($p->quiz_attempts) }}</div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs">
                <span class="text-slate-400 font-medium">Course IDs: {{ implode(', ', array_slice($p->course_ids, 0, 5)) }}{{ count($p->course_ids) > 5 ? '...' : '' }}</span>
                <a href="{{ route('projects.show', $p->id) }}" class="text-indigo-600 hover:text-indigo-700 font-semibold inline-flex items-center gap-1">
                    View Project →
                </a>
            </div>
        </div>
    @empty
        <div class="col-span-full bg-white rounded-xl shadow-card border border-slate-200/70 p-12 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                @include('partials.icon', ['name' => 'book', 'class' => 'w-6 h-6'])
            </div>
            <h3 class="text-sm font-semibold text-slate-800 mb-1">No Projects Found</h3>
            <p class="text-xs text-slate-500">No projects match the current filter selection.</p>
        </div>
    @endforelse
</div>

@endsection
