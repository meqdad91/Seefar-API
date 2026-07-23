@php
    $filterService = app(\App\Services\FilterService::class);
    $projectService = app(\App\Services\ProjectService::class);

    $projectsList = $projectService->getProjects();
    $coursesList = \Illuminate\Support\Facades\DB::table('course')
        ->where('id', '!=', 1)
        ->where('visible', 1)
        ->select('id', 'fullname')
        ->orderBy('fullname')
        ->get();

    $currentFilters = [
        'project_id' => request('project_id'),
        'course_id' => request('course_id'),
        'sex' => request('sex'),
        'country' => request('country'),
        'age_group' => request('age_group'),
        'origin' => request('origin'),
        'completion_status' => request('completion_status'),
        'start_date' => request('start_date'),
        'end_date' => request('end_date'),
    ];

    $hasActiveFilters = array_filter($currentFilters);

    $countries = [
        'IQ' => 'Iraq',
        'JO' => 'Jordan',
        'LB' => 'Lebanon',
        'SY' => 'Syrian Arab Republic',
        'EG' => 'Egypt',
        'PS' => 'Palestine',
        'YE' => 'Yemen',
        'SD' => 'Sudan',
        'TR' => 'Turkey',
    ];
@endphp

<div class="bg-white rounded-xl shadow-card border border-slate-200/70 p-4 mb-6" x-data="{ expanded: true }">
    <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-medium">
                @include('partials.icon', ['name' => 'funnel', 'class' => 'w-4 h-4'])
            </div>
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Dashboard & Analytics Filters</h3>
                <p class="text-xs text-slate-500">Filter data simultaneously across courses, demographics, projects, completion, and timeline</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if (!empty($hasActiveFilters))
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-600"></span>
                    {{ count($hasActiveFilters) }} Active Filter(s)
                </span>
            @endif
            <button type="button" class="text-slate-400 hover:text-slate-600 p-1">
                <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-180': expanded }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>
    </div>

    <form method="GET" action="{{ url()->current() }}" class="mt-4 pt-4 border-t border-slate-100" x-show="expanded" x-transition>
        @foreach (request()->except(['project_id', 'course_id', 'sex', 'country', 'age_group', 'origin', 'completion_status', 'start_date', 'end_date', 'page']) as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <!-- Project Filter -->
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Project</label>
                <select name="project_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 py-2 px-2.5">
                    <option value="">All Projects</option>
                    @foreach ($projectsList as $pId => $p)
                        <option value="{{ $p['id'] }}" {{ request('project_id') == $p['id'] ? 'selected' : '' }}>
                            {{ $p['name'] }} ({{ $p['course_count'] }} courses)
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Course Filter -->
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Course</label>
                <select name="course_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 py-2 px-2.5">
                    <option value="">All Courses</option>
                    @foreach ($coursesList as $c)
                        <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->fullname }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Sex of Beneficiary -->
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Sex of Beneficiary</label>
                <select name="sex" class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 py-2 px-2.5">
                    <option value="">All Sexes</option>
                    <option value="Male" {{ request('sex') === 'Male' ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ request('sex') === 'Female' ? 'selected' : '' }}>Female</option>
                    <option value="Other" {{ request('sex') === 'Other' ? 'selected' : '' }}>Other / Unspecified</option>
                </select>
            </div>

            <!-- Country / Region -->
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Country / Region</label>
                <select name="country" class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 py-2 px-2.5">
                    <option value="">All Countries</option>
                    @foreach ($countries as $code => $name)
                        <option value="{{ $code }}" {{ request('country') === $code ? 'selected' : '' }}>
                            {{ $name }} ({{ $code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Age Group -->
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Age Group</label>
                <select name="age_group" class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 py-2 px-2.5">
                    <option value="">All Age Groups</option>
                    <option value="<18" {{ request('age_group') === '<18' ? 'selected' : '' }}>Under 18</option>
                    <option value="18-24" {{ request('age_group') === '18-24' ? 'selected' : '' }}>18 – 24</option>
                    <option value="25-34" {{ request('age_group') === '25-34' ? 'selected' : '' }}>25 – 34</option>
                    <option value="35-49" {{ request('age_group') === '35-49' ? 'selected' : '' }}>35 – 49</option>
                    <option value="50+" {{ request('age_group') === '50+' ? 'selected' : '' }}>50+</option>
                </select>
            </div>

            <!-- Completion Status -->
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Completion Status</label>
                <select name="completion_status" class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 py-2 px-2.5">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('completion_status') === 'completed' ? 'selected' : '' }}>Completed Course(s)</option>
                    <option value="in_progress" {{ request('completion_status') === 'in_progress' ? 'selected' : '' }}>In Progress / Not Completed</option>
                </select>
            </div>

            <!-- Origin / Traffic Source -->
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Traffic Origin</label>
                <select name="origin" class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 py-2 px-2.5">
                    <option value="">All Origins</option>
                    <option value="web" {{ request('origin') === 'web' ? 'selected' : '' }}>Web Browser (web)</option>
                    <option value="ws" {{ request('origin') === 'ws' ? 'selected' : '' }}>Mobile App / Web Service (ws)</option>
                    <option value="cli" {{ request('origin') === 'cli' ? 'selected' : '' }}>CLI / Background (cli)</option>
                </select>
            </div>

            <!-- Start Date -->
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 py-2 px-2.5">
            </div>

            <!-- End Date -->
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 py-2 px-2.5">
            </div>

            <!-- Filter Actions -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-xs font-medium shadow-sm transition">
                    @include('partials.icon', ['name' => 'funnel', 'class' => 'w-3.5 h-3.5'])
                    Apply Filters
                </button>
                @if (!empty($hasActiveFilters))
                    <a href="{{ url()->current() }}" class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-600 text-xs font-medium transition">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>
</div>
