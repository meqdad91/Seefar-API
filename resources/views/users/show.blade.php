@extends('layouts.app', ['title' => trim($user->firstname.' '.$user->lastname) ?: $user->username])

@section('content')
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
            <div class="flex gap-4 mt-3 text-sm text-indigo-100">
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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

    <div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-6 lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-900">Enrolled courses</h2>
            <span class="text-xs text-slate-500">{{ $courses->count() }} {{ Str::plural('course', $courses->count()) }}</span>
        </div>
        @if ($courses->isEmpty())
            <p class="text-sm text-slate-500 text-center py-6">Not enrolled in any courses.</p>
        @else
            <div class="grid sm:grid-cols-2 gap-2.5">
                @foreach ($courses as $c)
                    <a href="{{ route('courses.show', $c->id) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/30 transition group">
                        <div class="w-8 h-8 rounded-md bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100 flex items-center justify-center flex-shrink-0">
                            @include('partials.icon', ['name' => 'book', 'class' => 'w-4 h-4'])
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-slate-900 truncate">{{ $c->fullname }}</div>
                            <div class="text-xs text-slate-500 truncate">{{ $c->shortname }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="flex items-center justify-between mt-7 mb-3">
            <h2 class="text-sm font-semibold text-slate-900">Recent grades</h2>
            <span class="text-xs text-slate-500">{{ $grades->count() }} {{ Str::plural('grade', $grades->count()) }}</span>
        </div>
        @if ($grades->isEmpty())
            <p class="text-sm text-slate-500 text-center py-6">No grades recorded.</p>
        @else
            <table class="w-full text-sm">
                <thead class="text-xs text-slate-500 uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="text-left py-2 font-medium">Item</th>
                        <th class="text-right py-2 font-medium">Final</th>
                        <th class="text-right py-2 font-medium">Max</th>
                        <th class="text-right py-2 font-medium">Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($grades as $g)
                        @php
                            $pct = ($g->finalgrade !== null && ($g->item->grademax ?? 0) > 0) ? round(100 * $g->finalgrade / $g->item->grademax) : null;
                            $tone = $pct === null ? 'slate' : ($pct >= 70 ? 'emerald' : ($pct >= 40 ? 'amber' : 'rose'));
                        @endphp
                        <tr>
                            <td class="py-2.5">{{ $g->item->itemname ?? '—' }}</td>
                            <td class="py-2.5 text-right tabular-nums font-semibold text-{{ $tone }}-700">
                                {{ $g->finalgrade !== null ? number_format($g->finalgrade, 2) : '—' }}
                            </td>
                            <td class="py-2.5 text-right tabular-nums text-slate-500">{{ $g->item->grademax ?? '—' }}</td>
                            <td class="py-2.5 text-right text-xs text-slate-500">
                                {{ $g->timemodified ? \Carbon\Carbon::createFromTimestamp($g->timemodified)->diffForHumans() : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
