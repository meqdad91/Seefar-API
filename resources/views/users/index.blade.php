@extends('layouts.app', ['title' => 'Users', 'subtitle' => number_format($users->total()) . ' users'])

@section('content')

@include('partials.filters')

<div class="bg-white rounded-xl shadow-card border border-slate-200/70 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100">
        <form method="GET" class="flex gap-2 max-w-xl">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    @include('partials.icon', ['name' => 'search', 'class' => 'w-4 h-4'])
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search username, email, or name..."
                       class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 focus:bg-white transition">
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">Search</button>
            @if ($search)
                <a href="{{ route('users.index') }}" class="px-3 py-2 text-sm text-slate-500 hover:text-slate-900">Clear</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-xs text-slate-500 uppercase tracking-wider border-b border-slate-100 bg-slate-50/50">
                <tr>
                    <th class="text-left px-6 py-3 font-medium">User</th>
                    <th class="text-left px-6 py-3 font-medium">Email</th>
                    <th class="text-left px-6 py-3 font-medium">Status</th>
                    <th class="text-left px-6 py-3 font-medium">Last access</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $u)
                    @php
                        $name = trim($u->firstname.' '.$u->lastname) ?: $u->username;
                        $initials = strtoupper(substr($u->firstname,0,1).substr($u->lastname,0,1)) ?: strtoupper(substr($u->username,0,2));
                        $isActive = $u->lastaccess && $u->lastaccess > (time() - 30*86400);
                    @endphp
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-100 to-indigo-200 text-indigo-700 text-xs font-semibold flex items-center justify-center flex-shrink-0">{{ $initials }}</div>
                                <div>
                                    <div class="font-medium text-slate-900">{{ $name }}</div>
                                    <div class="text-xs text-slate-500">@{{ $u->username }} · #{{ $u->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-slate-600">{{ $u->email }}</td>
                        <td class="px-6 py-3">
                            @if ($u->suspended)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700">Suspended</span>
                            @elseif ($isActive)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-slate-500 text-xs">
                            @if ($u->lastaccess)
                                {{ \Carbon\Carbon::createFromTimestamp($u->lastaccess)->diffForHumans() }}
                            @else
                                <span class="text-slate-400">Never</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right">
                            <a href="{{ route('users.show', $u->id) }}"
                               class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                                View
                                @include('partials.icon', ['name' => 'arrow-right', 'class' => 'w-3.5 h-3.5'])
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-6 py-3 border-t border-slate-100">
        {{ $users->links() }}
    </div>
</div>
@endsection
