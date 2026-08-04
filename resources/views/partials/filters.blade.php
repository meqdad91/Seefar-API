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

    $selectedProjects = (array) request('project_id', []);
    $selectedCourses = (array) request('course_id', []);
    $selectedSex = (array) request('sex', []);
    $selectedCountries = (array) request('country', []);
    $selectedAgeGroups = (array) request('age_group', []);
    $selectedStatus = (array) request('completion_status', []);
    $selectedOrigin = (array) request('origin', []);

    $activeFilterCount = 0;
    if (!empty(array_filter($selectedProjects))) $activeFilterCount++;
    if (!empty(array_filter($selectedCourses))) $activeFilterCount++;
    if (!empty(array_filter($selectedSex))) $activeFilterCount++;
    if (!empty(array_filter($selectedCountries))) $activeFilterCount++;
    if (!empty(array_filter($selectedAgeGroups))) $activeFilterCount++;
    if (!empty(array_filter($selectedStatus))) $activeFilterCount++;
    if (!empty(array_filter($selectedOrigin))) $activeFilterCount++;
    if (request('start_date')) $activeFilterCount++;
    if (request('end_date')) $activeFilterCount++;

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
                <p class="text-xs text-slate-500">Select multiple values simultaneously across projects, courses, demographics, status & timeline</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if ($activeFilterCount > 0)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-600"></span>
                    {{ $activeFilterCount }} Active Category Filter(s)
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
            @if (is_array($v))
                @foreach ($v as $item)
                    <input type="hidden" name="{{ $k }}[]" value="{{ $item }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endif
        @endforeach

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <!-- Project Multi-Select -->
            <div class="relative" x-data="{ open: false }">
                <label class="block text-xs font-medium text-slate-700 mb-1">Projects</label>
                <button type="button" @click="open = !open" @click.outside="open = false" class="w-full text-xs rounded-lg border border-slate-300 focus:border-indigo-500 bg-slate-50/50 py-2 px-2.5 flex items-center justify-between shadow-sm hover:bg-slate-100/70 transition">
                    <span class="truncate text-slate-700 font-medium">
                        @if (empty(array_filter($selectedProjects)))
                            All Projects
                        @elseif (count(array_filter($selectedProjects)) === 1)
                            1 Project Selected
                        @else
                            {{ count(array_filter($selectedProjects)) }} Projects Selected
                        @endif
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" x-transition class="absolute z-30 mt-1 w-full bg-white rounded-lg shadow-xl border border-slate-200 p-2 max-h-56 overflow-y-auto space-y-1 text-xs">
                    @foreach ($projectsList as $pId => $p)
                        <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 rounded-md cursor-pointer text-slate-700 font-normal">
                            <input type="checkbox" name="project_id[]" value="{{ $p['id'] }}"
                                {{ in_array((string)$p['id'], array_map('strval', $selectedProjects), true) ? 'checked' : '' }}
                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span class="truncate">{{ $p['name'] }} <span class="text-slate-400 text-[11px]">({{ $p['course_count'] }})</span></span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Course Multi-Select -->
            <div class="relative" x-data="{ open: false }">
                <label class="block text-xs font-medium text-slate-700 mb-1">Courses</label>
                <button type="button" @click="open = !open" @click.outside="open = false" class="w-full text-xs rounded-lg border border-slate-300 focus:border-indigo-500 bg-slate-50/50 py-2 px-2.5 flex items-center justify-between shadow-sm hover:bg-slate-100/70 transition">
                    <span class="truncate text-slate-700 font-medium">
                        @if (empty(array_filter($selectedCourses)))
                            All Courses
                        @elseif (count(array_filter($selectedCourses)) === 1)
                            1 Course Selected
                        @else
                            {{ count(array_filter($selectedCourses)) }} Courses Selected
                        @endif
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" x-transition class="absolute z-30 mt-1 w-full bg-white rounded-lg shadow-xl border border-slate-200 p-2 max-h-56 overflow-y-auto space-y-1 text-xs">
                    @foreach ($coursesList as $c)
                        <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 rounded-md cursor-pointer text-slate-700 font-normal">
                            <input type="checkbox" name="course_id[]" value="{{ $c->id }}"
                                {{ in_array((string)$c->id, array_map('strval', $selectedCourses), true) ? 'checked' : '' }}
                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span class="truncate">{{ $c->fullname }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Sex of Beneficiary Multi-Select -->
            <div class="relative" x-data="{ open: false }">
                <label class="block text-xs font-medium text-slate-700 mb-1">Sex of Beneficiary</label>
                <button type="button" @click="open = !open" @click.outside="open = false" class="w-full text-xs rounded-lg border border-slate-300 focus:border-indigo-500 bg-slate-50/50 py-2 px-2.5 flex items-center justify-between shadow-sm hover:bg-slate-100/70 transition">
                    <span class="truncate text-slate-700 font-medium">
                        @if (empty(array_filter($selectedSex)))
                            All Sexes
                        @elseif (count(array_filter($selectedSex)) === 1)
                            1 Selected
                        @else
                            {{ count(array_filter($selectedSex)) }} Selected
                        @endif
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" x-transition class="absolute z-30 mt-1 w-full bg-white rounded-lg shadow-xl border border-slate-200 p-2 max-h-56 overflow-y-auto space-y-1 text-xs">
                    @foreach (['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other / Unspecified'] as $sVal => $sLabel)
                        <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 rounded-md cursor-pointer text-slate-700 font-normal">
                            <input type="checkbox" name="sex[]" value="{{ $sVal }}"
                                {{ in_array($sVal, $selectedSex, true) ? 'checked' : '' }}
                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span class="truncate">{{ $sLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Country / Region Multi-Select -->
            <div class="relative" x-data="{ open: false }">
                <label class="block text-xs font-medium text-slate-700 mb-1">Country / Region</label>
                <button type="button" @click="open = !open" @click.outside="open = false" class="w-full text-xs rounded-lg border border-slate-300 focus:border-indigo-500 bg-slate-50/50 py-2 px-2.5 flex items-center justify-between shadow-sm hover:bg-slate-100/70 transition">
                    <span class="truncate text-slate-700 font-medium">
                        @if (empty(array_filter($selectedCountries)))
                            All Countries
                        @elseif (count(array_filter($selectedCountries)) === 1)
                            1 Country Selected
                        @else
                            {{ count(array_filter($selectedCountries)) }} Countries Selected
                        @endif
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" x-transition class="absolute z-30 mt-1 w-full bg-white rounded-lg shadow-xl border border-slate-200 p-2 max-h-56 overflow-y-auto space-y-1 text-xs">
                    @foreach ($countries as $code => $name)
                        <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 rounded-md cursor-pointer text-slate-700 font-normal">
                            <input type="checkbox" name="country[]" value="{{ $code }}"
                                {{ in_array($code, $selectedCountries, true) ? 'checked' : '' }}
                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span class="truncate">{{ $name }} ({{ $code }})</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Age Group Multi-Select -->
            <div class="relative" x-data="{ open: false }">
                <label class="block text-xs font-medium text-slate-700 mb-1">Age Group</label>
                <button type="button" @click="open = !open" @click.outside="open = false" class="w-full text-xs rounded-lg border border-slate-300 focus:border-indigo-500 bg-slate-50/50 py-2 px-2.5 flex items-center justify-between shadow-sm hover:bg-slate-100/70 transition">
                    <span class="truncate text-slate-700 font-medium">
                        @if (empty(array_filter($selectedAgeGroups)))
                            All Age Groups
                        @elseif (count(array_filter($selectedAgeGroups)) === 1)
                            1 Age Group Selected
                        @else
                            {{ count(array_filter($selectedAgeGroups)) }} Age Groups Selected
                        @endif
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" x-transition class="absolute z-30 mt-1 w-full bg-white rounded-lg shadow-xl border border-slate-200 p-2 max-h-56 overflow-y-auto space-y-1 text-xs">
                    @foreach (['<18' => 'Under 18', '18-24' => '18 – 24', '25-34' => '25 – 34', '35-49' => '35 – 49', '50+' => '50+'] as $agVal => $agLabel)
                        <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 rounded-md cursor-pointer text-slate-700 font-normal">
                            <input type="checkbox" name="age_group[]" value="{{ $agVal }}"
                                {{ in_array($agVal, $selectedAgeGroups, true) ? 'checked' : '' }}
                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span class="truncate">{{ $agLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Completion Status Multi-Select -->
            <div class="relative" x-data="{ open: false }">
                <label class="block text-xs font-medium text-slate-700 mb-1">Completion Status</label>
                <button type="button" @click="open = !open" @click.outside="open = false" class="w-full text-xs rounded-lg border border-slate-300 focus:border-indigo-500 bg-slate-50/50 py-2 px-2.5 flex items-center justify-between shadow-sm hover:bg-slate-100/70 transition">
                    <span class="truncate text-slate-700 font-medium">
                        @if (empty(array_filter($selectedStatus)))
                            All Statuses
                        @elseif (count(array_filter($selectedStatus)) === 1)
                            1 Status Selected
                        @else
                            {{ count(array_filter($selectedStatus)) }} Statuses Selected
                        @endif
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" x-transition class="absolute z-30 mt-1 w-full bg-white rounded-lg shadow-xl border border-slate-200 p-2 max-h-56 overflow-y-auto space-y-1 text-xs">
                    @foreach (['completed' => 'Completed Course(s)', 'in_progress' => 'In Progress / Not Completed'] as $stVal => $stLabel)
                        <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 rounded-md cursor-pointer text-slate-700 font-normal">
                            <input type="checkbox" name="completion_status[]" value="{{ $stVal }}"
                                {{ in_array($stVal, $selectedStatus, true) ? 'checked' : '' }}
                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span class="truncate">{{ $stLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Origin / Traffic Source Multi-Select -->
            <div class="relative" x-data="{ open: false }">
                <label class="block text-xs font-medium text-slate-700 mb-1">Traffic Origin</label>
                <button type="button" @click="open = !open" @click.outside="open = false" class="w-full text-xs rounded-lg border border-slate-300 focus:border-indigo-500 bg-slate-50/50 py-2 px-2.5 flex items-center justify-between shadow-sm hover:bg-slate-100/70 transition">
                    <span class="truncate text-slate-700 font-medium">
                        @if (empty(array_filter($selectedOrigin)))
                            All Origins
                        @elseif (count(array_filter($selectedOrigin)) === 1)
                            1 Origin Selected
                        @else
                            {{ count(array_filter($selectedOrigin)) }} Origins Selected
                        @endif
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" x-transition class="absolute z-30 mt-1 w-full bg-white rounded-lg shadow-xl border border-slate-200 p-2 max-h-56 overflow-y-auto space-y-1 text-xs">
                    @foreach (['web' => 'Web Browser (web)', 'ws' => 'Mobile App / Web Service (ws)', 'cli' => 'CLI / Background (cli)'] as $oriVal => $oriLabel)
                        <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 rounded-md cursor-pointer text-slate-700 font-normal">
                            <input type="checkbox" name="origin[]" value="{{ $oriVal }}"
                                {{ in_array($oriVal, $selectedOrigin, true) ? 'checked' : '' }}
                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span class="truncate">{{ $oriLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Start Date -->
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full text-xs rounded-lg border border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 py-2 px-2.5">
            </div>

            <!-- End Date -->
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full text-xs rounded-lg border border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 py-2 px-2.5">
            </div>

            <!-- Filter Actions -->
            <div class="flex items-end gap-2 col-span-1 sm:col-span-2 lg:col-span-2">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-xs font-medium shadow-sm transition">
                    @include('partials.icon', ['name' => 'funnel', 'class' => 'w-3.5 h-3.5'])
                    Apply Multi-Select Filters
                </button>
                @if ($activeFilterCount > 0)
                    <a href="{{ url()->current() }}" class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-600 text-xs font-medium transition">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>
</div>
