@extends('layouts.app', ['title' => 'Courses', 'subtitle' => number_format($courses->total()) . ' visible courses'])

@section('content')
<div class="bg-white rounded-xl shadow-card border border-slate-200/70 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100">
        <form method="GET" class="flex gap-2 max-w-xl">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    @include('partials.icon', ['name' => 'search', 'class' => 'w-4 h-4'])
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search course name or short name..."
                       class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 focus:bg-white transition">
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">Search</button>
            @if ($search)
                <a href="{{ route('courses.index') }}" class="px-3 py-2 text-sm text-slate-500 hover:text-slate-900">Clear</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-xs text-slate-500 uppercase tracking-wider border-b border-slate-100 bg-slate-50/50">
                <tr>
                    <th class="text-left px-6 py-3 font-medium">Course</th>
                    <th class="text-left px-6 py-3 font-medium">Category</th>
                    <th class="text-left px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($courses as $c)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-sky-100 to-sky-200 text-sky-700 flex items-center justify-center flex-shrink-0">
                                    @include('partials.icon', ['name' => 'book', 'class' => 'w-4 h-4'])
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-slate-900 truncate">{{ $c->fullname }}</div>
                                    <div class="text-xs text-slate-500">{{ $c->shortname }} · #{{ $c->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-slate-600">{{ $c->categoryInfo->name ?? '—' }}</td>
                        <td class="px-6 py-3">
                            @if ($c->visible)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Visible
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Hidden</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right">
                            <a href="{{ route('courses.show', $c->id) }}"
                               class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                                View
                                @include('partials.icon', ['name' => 'arrow-right', 'class' => 'w-3.5 h-3.5'])
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-12 text-center text-slate-500">No courses found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-6 py-3 border-t border-slate-100">
        {{ $courses->links() }}
    </div>
</div>
@endsection
