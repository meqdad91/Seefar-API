@extends('layouts.app', ['title' => trim($user->firstname.' '.$user->lastname) ?: $user->username])

@section('content')

@if ($scopedCourseId)
    <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-3.5 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs text-indigo-900 font-medium">
            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
            Profile view scoped specifically to Course #{{ $scopedCourseId }}
        </div>
        <a href="{{ route('users.show', $user->id) }}" class="text-xs text-indigo-600 font-semibold hover:underline">
            View All Courses →
        </a>
    </div>
@endif

<a href="{{ route('users.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 mb-4">
    @include('partials.icon', ['name' => 'arrow-left', 'class' => 'w-4 h-4'])
    Back to users
</a>

@php
    $name = trim($user->firstname.' '.$user->lastname) ?: $user->username;
    $initials = strtoupper(substr($user->firstname,0,1).substr($user->lastname,0,1)) ?: strtoupper(substr($user->username,0,2));
    $isActive = $user->lastaccess && $user->lastaccess > (time() - 30*86400);
@endphp

<div class="bg-gradient-to-br from-indigo-600 to-indigo-800 rounded-xl p-6 text-white shadow-lg shadow-indigo-600/20 mb-6">
    <div class="flex items-start gap-5">
        <div class="w-16 h-16 rounded-full bg-white/15 backdrop-blur ring-2 ring-white/20 text-white text-lg font-semibold flex items-center justify-center flex-shrink-0">{{ $initials }}</div>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-xl font-semibold">{{ $name }}</h2>
                @if ($user->suspended)
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-rose-500/20 text-rose-100">Suspended</span>
                @elseif ($isActive)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/20 text-emerald-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-300"></span> Active
                    </span>
                @endif
            </div>
            <div class="text-sm text-indigo-100 mt-1">@{{ $user->username }} · #{{ $user->id }}</div>
            <div class="flex gap-4 mt-3 text-sm text-indigo-100 flex-wrap">
                @if ($user->email)
                    <span class="inline-flex items-center gap-1.5">
                        @include('partials.icon', ['name' => 'envelope', 'class' => 'w-4 h-4'])
                        {{ $user->email }}
                    </span>
                @endif
                @if ($user->institution)
                    <span class="inline-flex items-center gap-1.5">
                        @include('partials.icon', ['name' => 'building', 'class' => 'w-4 h-4'])
                        {{ $user->institution }}
                    </span>
                @endif
                @if ($user->country)
                    <span class="inline-flex items-center gap-1.5">
                        @include('partials.icon', ['name' => 'globe', 'class' => 'w-4 h-4'])
                        {{ $user->country }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Profile details -->
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Profile details</h2>
        <dl class="space-y-3 text-sm">
            @php
                $fields = [
                    'ID number'   => $user->idnumber ?: '—',
                    'Department'  => $user->department ?: '—',
                    'City'        => $user->city ?: '—',
                    'Language'    => $user->lang,
                    'Timezone'    => $user->timezone,
                    'First access'=> $user->firstaccess ? \Carbon\Carbon::createFromTimestamp($user->firstaccess)->toDayDateTimeString() : 'Never',
                    'Last access' => $user->lastaccess ? \Carbon\Carbon::createFromTimestamp($user->lastaccess)->toDayDateTimeString() : 'Never',
                    'Last login'  => $user->lastlogin ? \Carbon\Carbon::createFromTimestamp($user->lastlogin)->toDayDateTimeString() : 'Never',
                ];
            @endphp
            @foreach ($fields as $label => $value)
                <div class="flex justify-between gap-3 py-1.5 border-b border-slate-100 last:border-0">
                    <dt class="text-slate-500">{{ $label }}</dt>
                    <dd class="text-right text-slate-800 font-medium truncate">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <!-- Grades & Knowledge Gain by Course -->
    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6 lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-900">Grades & Knowledge Gain by Course</h2>
            <span class="text-xs text-slate-500">{{ count($courseGains) }} {{ Str::plural('course', count($courseGains)) }}</span>
        </div>

        @if (empty($courseGains))
            <p class="text-sm text-slate-500 text-center py-6">Not enrolled in any courses.</p>
        @else
            <div class="space-y-3">
                @foreach ($courseGains as $cg)
                    @php
                        $isTarget = $scopedCourseId && $scopedCourseId === $cg->course->id;
                    @endphp
                    <div class="rounded-xl border {{ $isTarget ? 'border-indigo-400 bg-indigo-50/20 shadow-sm' : 'border-slate-200 bg-slate-50/40' }} p-4">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div>
                                <a href="{{ route('courses.show', $cg->course->id) }}" class="text-sm font-semibold text-slate-900 hover:text-indigo-600">
                                    {{ $cg->course->fullname }}
                                </a>
                                <div class="text-xs text-slate-400 mt-0.5">Shortname: {{ $cg->course->shortname }} | Course ID: {{ $cg->course->id }}</div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cg->is_completed ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                {{ $cg->is_completed ? 'Completed' : 'In Progress' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-3 gap-3 py-2.5 px-3 bg-white rounded-lg border border-slate-100 text-center text-xs">
                            <div>
                                <div class="text-[11px] text-slate-400 uppercase font-medium">Pre-Test Grade</div>
                                <div class="text-sm font-semibold text-slate-800 tabular-nums mt-0.5">
                                    {{ $cg->pre_score !== null ? $cg->pre_score . '%' : '—' }}
                                </div>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-400 uppercase font-medium">Post-Test Grade</div>
                                <div class="text-sm font-semibold text-slate-800 tabular-nums mt-0.5">
                                    {{ $cg->post_score !== null ? $cg->post_score . '%' : '—' }}
                                </div>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-400 uppercase font-medium">% Knowledge Gain</div>
                                <div class="text-sm font-bold tabular-nums mt-0.5 {{ $cg->gain === null ? 'text-slate-400' : ($cg->gain >= 0 ? 'text-emerald-600' : 'text-rose-600') }}">
                                    @if ($cg->gain !== null)
                                        {{ $cg->gain >= 0 ? '+' : '' }}{{ $cg->gain }}%
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- Recent Grades Table (Latest to Oldest) -->
<div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Recent Grades & Assessment Results</h2>
            <p class="text-xs text-slate-500 mt-0.5">Sorted from latest to oldest with course attribution</p>
        </div>
        <span class="text-xs text-slate-500">{{ $grades->count() }} {{ Str::plural('result', $grades->count()) }}</span>
    </div>

    @if ($grades->isEmpty())
        <p class="text-sm text-slate-500 text-center py-6">No grades recorded.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-slate-500 uppercase tracking-wider border-b border-slate-100 bg-slate-50/50">
                    <tr>
                        <th class="text-left px-4 py-2.5 font-medium">Course</th>
                        <th class="text-left px-4 py-2.5 font-medium">Assessment / Item</th>
                        <th class="text-right px-4 py-2.5 font-medium">Final Grade</th>
                        <th class="text-right px-4 py-2.5 font-medium">Max Grade</th>
                        <th class="text-right px-4 py-2.5 font-medium">Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @foreach ($grades as $g)
                        @php
                            $pct = ($g->finalgrade !== null && ($g->grademax ?? 0) > 0) ? round(100 * $g->finalgrade / $g->grademax) : null;
                            $tone = $pct === null ? 'slate' : ($pct >= 70 ? 'emerald' : ($pct >= 40 ? 'amber' : 'rose'));
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900">
                                {{ $g->course_name ?: 'General / Main Course' }}
                            </td>
                            <td class="px-4 py-3 text-slate-700 font-medium">{{ $g->itemname ?: ($g->item->itemname ?? 'Course Grade') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums font-bold text-{{ $tone }}-600">
                                {{ $g->finalgrade !== null ? number_format($g->finalgrade, 2) : '—' }}
                                @if ($pct !== null)
                                    <span class="text-[11px] font-normal text-slate-400">({{ $pct }}%)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-500">{{ $g->grademax ? number_format($g->grademax, 0) : '—' }}</td>
                            <td class="px-4 py-3 text-right text-xs text-slate-400 whitespace-nowrap">
                                {{ $g->timemodified ? \Carbon\Carbon::createFromTimestamp($g->timemodified)->diffForHumans() : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
