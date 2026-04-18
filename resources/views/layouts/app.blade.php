<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard' }} — Moodle Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] },
                    colors: {
                        brand: {
                            50:'#eef2ff', 100:'#e0e7ff', 500:'#6366f1', 600:'#4f46e5', 700:'#4338ca'
                        }
                    },
                    boxShadow: {
                        card: '0 1px 2px 0 rgb(0 0 0 / 0.04), 0 1px 3px 0 rgb(0 0 0 / 0.06)',
                    }
                }
            }
        };
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>body{font-feature-settings:'cv11','ss01';}</style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen">
<div class="flex min-h-screen">
    <aside class="w-64 bg-gradient-to-b from-slate-900 to-slate-950 text-slate-200 flex flex-col">
        <div class="px-5 py-5 border-b border-white/10">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center shadow-lg shadow-brand-600/30">
                    @include('partials.icon', ['name' => 'sparkles', 'class' => 'w-5 h-5 text-white'])
                </div>
                <div>
                    <div class="text-sm font-semibold text-white">Moodle Admin</div>
                    <div class="text-[11px] text-slate-400 -mt-0.5">Dashboard v1.0</div>
                </div>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1">
            @php
                $links = [
                    ['route' => 'dashboard',     'label' => 'Dashboard',  'icon' => 'home'],
                    ['route' => 'engagement',    'label' => 'Engagement', 'icon' => 'chart-bar'],
                    ['route' => 'quizzes',       'label' => 'Quizzes',    'icon' => 'pencil'],
                    ['route' => 'atrisk',        'label' => 'At-risk',    'icon' => 'fire'],
                    ['route' => 'cohorts',       'label' => 'Cohorts',    'icon' => 'users'],
                    ['route' => 'languages',     'label' => 'Languages',  'icon' => 'globe'],
                    ['route' => 'users.index',   'label' => 'Users',      'icon' => 'users'],
                    ['route' => 'courses.index', 'label' => 'Courses',    'icon' => 'book'],
                ];
            @endphp
            @foreach ($links as $l)
                @php
                    $active = request()->routeIs($l['route']) || request()->routeIs(str_replace('.index','.*',$l['route']));
                @endphp
                <a href="{{ route($l['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition
                          {{ $active
                              ? 'bg-white/10 text-white shadow-sm'
                              : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    @include('partials.icon', ['name' => $l['icon'], 'class' => 'w-5 h-5 ' . ($active ? 'text-brand-400' : '')])
                    <span class="font-medium">{{ $l['label'] }}</span>
                    @if ($active)
                        <span class="ml-auto w-1.5 h-1.5 rounded-full bg-brand-400"></span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="px-3 pb-4">
            <div class="px-3 py-3 mb-2 rounded-lg bg-white/5 border border-white/5">
                <div class="text-[11px] uppercase tracking-wider text-slate-500">Signed in as</div>
                <div class="text-sm font-medium text-white truncate mt-0.5">{{ session('admin_full_name') ?: session('admin_username') }}</div>
                <div class="text-[11px] text-slate-400 truncate">{{ session('admin_username') }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-400 hover:bg-white/5 hover:text-white transition">
                    @include('partials.icon', ['name' => 'logout', 'class' => 'w-5 h-5'])
                    <span class="font-medium">Log out</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 overflow-x-auto">
        <header class="bg-white border-b border-slate-200 px-8 py-5">
            <h1 class="text-xl font-semibold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
            @isset($subtitle)
                <p class="text-sm text-slate-500 mt-0.5">{{ $subtitle }}</p>
            @endisset
        </header>
        <div class="p-8">
            {{ $slot ?? '' }}
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
