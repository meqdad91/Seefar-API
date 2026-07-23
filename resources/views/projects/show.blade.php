@extends('layouts.app', ['title' => $project['name'], 'subtitle' => 'Project details and mapped courses'])

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="{{ route('projects.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium inline-flex items-center gap-1 mb-2">
            ← Back to All Projects
        </a>
        <h2 class="text-xl font-bold text-slate-900">{{ $project['name'] }}</h2>
        <p class="text-xs text-slate-500 mt-0.5">{{ $project['description'] }}</p>
    </div>
    <span class="px-3 py-1 bg-brand-50 text-brand-700 text-xs font-semibold rounded-full border border-brand-200">
        {{ count($courses) }} Courses Assigned
    </span>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-5">
        <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Learners Enrolled</div>
        <div class="text-2xl font-semibold text-slate-900 mt-2 tabular-nums">{{ number_format($enrolments) }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-5">
        <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Completions</div>
        <div class="text-2xl font-semibold text-emerald-600 mt-2 tabular-nums">{{ number_format($completions) }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-5">
        <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Quiz Attempts</div>
        <div class="text-2xl font-semibold text-amber-600 mt-2 tabular-nums">{{ number_format($quizAttempts) }}</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-card border border-slate-200/70 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-900">Project Courses List</h3>
        <span class="text-xs text-slate-500">{{ count($courses) }} total courses</span>
    </div>
    <div class="divide-y divide-slate-100">
        @forelse ($courses as $c)
            <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition">
                <div>
                    <a href="{{ route('courses.show', $c->id) }}" class="text-sm font-semibold text-slate-900 hover:text-indigo-600">
                        {{ $c->fullname }}
                    </a>
                    <div class="text-xs text-slate-400 mt-0.5">Shortname: {{ $c->shortname }} | Course ID: {{ $c->id }}</div>
                </div>
                <a href="{{ route('courses.show', $c->id) }}" class="text-xs text-indigo-600 font-semibold hover:underline">
                    View Course Details →
                </a>
            </div>
        @empty
            <div class="px-6 py-8 text-center text-slate-500 text-xs">No courses currently mapped to this project.</div>
        @endforelse
    </div>
</div>

@endsection
