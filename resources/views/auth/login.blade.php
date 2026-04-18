<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign in — Moodle Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] } } } };
    </script>
</head>
<body class="bg-slate-50 font-sans antialiased min-h-screen flex">

<aside class="hidden lg:flex flex-col justify-between w-1/2 p-12 text-white relative overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-slate-900">
    <div class="absolute inset-0 opacity-30 [background:radial-gradient(circle_at_top_right,rgba(255,255,255,0.25),transparent_60%)]"></div>
    <div class="relative">
        <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-lg bg-white/15 backdrop-blur flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6 text-white">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/>
                </svg>
            </div>
            <span class="text-lg font-semibold">Moodle Admin</span>
        </div>
    </div>
    <div class="relative">
        <h2 class="text-3xl font-semibold leading-tight max-w-md">Insight into every learner, every course.</h2>
        <p class="text-indigo-100 mt-3 max-w-md">A unified admin dashboard for users, enrolments, grades, completion and quiz performance — pulled live from Moodle.</p>
    </div>
    <div class="relative text-xs text-indigo-200/80">© {{ date('Y') }} Moodle Admin Dashboard</div>
</aside>

<main class="flex-1 flex items-center justify-center p-6">
    <form method="POST" action="{{ route('login.post') }}" class="w-full max-w-sm">
        @csrf

        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-900">Welcome back</h1>
            <p class="text-sm text-slate-500 mt-1">Sign in with your Moodle credentials.</p>
        </div>

        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-700 text-sm px-3 py-2.5 rounded-lg mb-4 flex gap-2">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus
                       class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                <input type="password" name="password" required
                       class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition">
            </div>
        </div>

        <button type="submit"
                class="w-full mt-6 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-lg px-3 py-2.5 text-sm font-semibold hover:from-indigo-700 hover:to-indigo-800 shadow-lg shadow-indigo-600/20 transition">
            Sign in
        </button>

        <p class="text-xs text-slate-400 text-center mt-6">
            Restricted to Moodle site administrators and managers.
        </p>
    </form>
</main>

</body>
</html>
