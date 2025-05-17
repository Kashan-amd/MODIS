<x-layouts.app :title="__('Event Dashboard')">
    <!-- Header with welcome message and date -->
    {{-- <div
        class="absolute right-0 top-0 h-32 w-32 -translate-y-12 -translate-x-5 rotate-12 transform rounded-full bg-red-500/10 blur-xl">
    </div> --}}
    <div class="mb-6 flex items-center justify-between rounded-xl bg-white p-6 shadow-md dark:bg-zinc-800">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white sm:text-3xl">Event Dashboard</h1>
        </div>
        <div class="flex items-center gap-3">
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Today is</p>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white sm:text-2xl">May 14, 2025</h1>
        </div>
    </div>

    <div class="flex w-full flex-1 flex-col gap-6 rounded-xl pb-6 ">
        <!-- Summary Cards Row - Glass morphism design -->
        <div class="grid auto-rows-min gap-4 md:grid-cols-4">
            <!-- Upcoming Events Card -->
            <div
                class="group relative overflow-hidden rounded-2xl border border-indigo-100/50 bg-gradient-to-br from-white to-indigo-50/30 p-6 shadow-lg transition-all hover:shadow-xl dark:border-indigo-900/30 dark:from-neutral-800 dark:to-indigo-900/20">
                <div
                    class="absolute right-0 top-0 h-32 w-32 -translate-y-12 translate-x-12 rotate-12 transform rounded-full bg-indigo-500/10 ">
                </div>
                <div
                    class="absolute left-0 bottom-0 h-20 w-20 translate-y-10 -translate-x-10 transform rounded-full bg-indigo-500/10 blur-lg">
                </div>
                <div class="relative">
                    <div
                        class="mb-4 inline-flex rounded-xl bg-indigo-100 p-3 text-indigo-600 ring-4 ring-indigo-100/30 dark:bg-indigo-900/50 dark:text-indigo-400 dark:ring-indigo-900/20">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white">Upcoming Events
                    </h3>
                    <div class="flex items-baseline">
                        <span class="text-3xl font-bold text-gray-800 dark:text-white">24</span>
                        <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">This Month</span>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="flex items-center text-green-600 dark:text-green-400">
                            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                            12% increase
                        </span>
                        <span class="ml-2 text-gray-500 dark:text-gray-400">from last month</span>
                    </div>
                </div>
            </div>

            <!-- Total Revenue Card -->
            <div
                class="group relative overflow-hidden rounded-2xl border border-emerald-100/50 bg-gradient-to-br from-white to-emerald-50/30 p-6 shadow-lg transition-all hover:shadow-xl dark:border-emerald-900/30 dark:from-neutral-800 dark:to-emerald-900/20">
                <div
                    class="absolute right-0 top-0 h-32 w-32 -translate-y-12 translate-x-12 rotate-12 transform rounded-full bg-emerald-500/10 blur-xl">
                </div>
                <div
                    class="absolute left-0 bottom-0 h-20 w-20 translate-y-10 -translate-x-10 transform rounded-full bg-emerald-500/10 blur-lg">
                </div>
                <div class="relative">
                    <div
                        class="mb-4 inline-flex rounded-xl bg-emerald-100 p-3 text-emerald-600 ring-4 ring-emerald-100/30 dark:bg-emerald-900/50 dark:text-emerald-400 dark:ring-emerald-900/20">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white">Total Revenue Booked</h3>
                    <div class="flex items-baseline">
                        <span class="text-3xl font-bold text-gray-800 dark:text-white">PKR 187,500</span>
                        <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">This Quarter</span>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="flex items-center text-green-600 dark:text-green-400">
                            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                            8.3% increase
                        </span>
                        <span class="ml-2 text-gray-500 dark:text-gray-400">from last quarter</span>
                    </div>
                </div>
            </div>

            <!-- Event Registrations Card -->
            <div
                class="group relative overflow-hidden rounded-2xl border border-purple-100/50 bg-gradient-to-br from-white to-purple-50/30 p-6 shadow-lg transition-all hover:shadow-xl dark:border-purple-900/30 dark:from-neutral-800 dark:to-purple-900/20">
                <div
                    class="absolute right-0 top-0 h-32 w-32 -translate-y-12 translate-x-12 rotate-12 transform rounded-full bg-purple-500/10 blur-xl">
                </div>
                <div
                    class="absolute left-0 bottom-0 h-20 w-20 translate-y-10 -translate-x-10 transform rounded-full bg-purple-500/10 blur-lg">
                </div>
                <div class="relative">
                    <div
                        class="mb-4 inline-flex rounded-xl bg-purple-100 p-3 text-purple-600 ring-4 ring-purple-100/30 dark:bg-purple-900/50 dark:text-purple-400 dark:ring-purple-900/20">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white">Total Expenses Booked</h3>
                    <div class="flex items-baseline">
                        <span class="text-3xl font-bold text-gray-800 dark:text-white">PKR 111,248</span>
                        <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">This Month</span>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="flex items-center text-green-600 dark:text-green-400">
                            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                            15% increase
                        </span>
                        <span class="ml-2 text-gray-500 dark:text-gray-400">from last month</span>
                    </div>
                </div>
            </div>

            <!-- Venues Card -->
            <div
                class="group relative overflow-hidden rounded-2xl border border-amber-100/50 bg-gradient-to-br from-white to-amber-50/30 p-6 shadow-lg transition-all hover:shadow-xl dark:border-amber-900/30 dark:from-neutral-800 dark:to-amber-900/20">
                <div
                    class="absolute right-0 top-0 h-32 w-32 -translate-y-12 translate-x-12 rotate-12 transform rounded-full bg-amber-500/10 blur-xl">
                </div>
                <div
                    class="absolute left-0 bottom-0 h-20 w-20 translate-y-10 -translate-x-10 transform rounded-full bg-amber-500/10 blur-lg">
                </div>
                <div class="relative">
                    <div
                        class="mb-4 inline-flex rounded-xl bg-amber-100 p-3 text-amber-600 ring-4 ring-amber-100/30 dark:bg-amber-900/50 dark:text-amber-400 dark:ring-amber-900/20">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                            </path>
                        </svg>
                    </div>
                    <h3 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white">Venues</h3>
                    <div class="flex items-baseline">
                        <span class="text-3xl font-bold text-gray-800 dark:text-white">7</span>
                        <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">This Month</span>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="flex items-center text-green-600 dark:text-green-400">
                            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                            10% increase
                        </span>
                        <span class="ml-2 text-gray-500 dark:text-gray-400">from last month</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area - Revamped with  layout -->
        <div class="grid gap-6">
            <!-- Key Performance Metrics - Full Width -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="rounded-2xl border p-1 shadow-md hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                    <div
                        class="border-b border-neutral-200 bg-white px-6 py-4 dark:border-neutral-700 dark:bg-neutral-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Upcoming Events</h3>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="inline-flex overflow-hidden rounded-lg bg-gray-100 p-0.5 dark:bg-gray-700">
                                    <button
                                        class="px-3 py-1 text-xs font-medium rounded-md transition-colors hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300">
                                        Week
                                    </button>
                                    <button
                                        class="px-3 py-1 text-xs font-medium rounded-md transition-colors bg-indigo-600 text-white hover:bg-indigo-700">
                                        Month
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Events Timeline - Enhanced with  design and interactions -->
                    <div
                        class="divide-y divide-neutral-100 dark:divide-neutral-700/50 px-6 py-2 max-h-[240px] overflow-y-auto">
                        <!-- Annual Tech Conference -->
                        <div
                            class="group flex items-center py-3 transition-all hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 rounded-lg">
                            <div class="mr-4 flex-shrink-0 relative">
                                <div
                                    class="rounded-xl bg-blue-100 p-2.5 text-blue-600 ring-4 ring-blue-50/50 dark:bg-blue-900/50 dark:text-blue-300 dark:ring-blue-900/20">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div
                                    class="absolute -bottom-1 -right-1 h-4 w-4 rounded-full border-2 border-white bg-green-500 dark:border-gray-800">
                                </div>
                            </div>
                            <div class="flex-grow">
                                <div class="flex items-center justify-between">
                                    <h4
                                        class="text-sm font-medium text-gray-800 group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">
                                        Annual Tech Conference
                                    </h4>
                                </div>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <div class="flex items-center gap-1">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">May 20, 2025</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                            </path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Convention Center</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z">
                                            </path>
                                        </svg>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">450 attendees</span>
                                    </div>
                                </div>
                            </div>
                            <button
                                class="ml-2 rounded-full p-1 text-gray-400 opacity-0 transition-opacity hover:bg-gray-100 hover:text-gray-600 group-hover:opacity-100 dark:hover:bg-gray-800 dark:hover:text-gray-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z">
                                    </path>
                                </svg>
                            </button>
                        </div>

                        <!-- Marketing Summit -->
                        <div
                            class="group flex items-center py-3 transition-all hover:bg-purple-50/30 dark:hover:bg-purple-900/10 rounded-lg">
                            <div class="mr-4 flex-shrink-0 relative">
                                <div
                                    class="rounded-xl bg-purple-100 p-2.5 text-purple-600 ring-4 ring-purple-50/50 dark:bg-purple-900/50 dark:text-purple-300 dark:ring-purple-900/20">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div
                                    class="absolute -bottom-1 -right-1 h-4 w-4 rounded-full border-2 border-white bg-yellow-400 dark:border-gray-800">
                                </div>
                            </div>
                            <div class="flex-grow">
                                <div class="flex items-center justify-between">
                                    <h4
                                        class="text-sm font-medium text-gray-800 group-hover:text-purple-600 dark:text-white dark:group-hover:text-purple-400">
                                        Marketing Summit
                                    </h4>
                                </div>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <div class="flex items-center gap-1">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">May 25, 2025</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                            </path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Grand Hotel</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z">
                                            </path>
                                        </svg>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">200 attendees</span>
                                    </div>
                                </div>
                            </div>
                            <button
                                class="ml-2 rounded-full p-1 text-gray-400 opacity-0 transition-opacity hover:bg-gray-100 hover:text-gray-600 group-hover:opacity-100 dark:hover:bg-gray-800 dark:hover:text-gray-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z">
                                    </path>
                                </svg>
                            </button>
                        </div>

                        <!-- Product Launch -->
                        <div
                            class="group flex items-center py-3 transition-all hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 rounded-lg">
                            <div class="mr-4 flex-shrink-0 relative">
                                <div
                                    class="rounded-xl bg-indigo-100 p-2.5 text-indigo-600 ring-4 ring-indigo-50/50 dark:bg-indigo-900/50 dark:text-indigo-300 dark:ring-indigo-900/20">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div
                                    class="absolute -bottom-1 -right-1 h-4 w-4 rounded-full border-2 border-white bg-green-500 dark:border-gray-800">
                                </div>
                            </div>
                            <div class="flex-grow">
                                <div class="flex items-center justify-between">
                                    <h4
                                        class="text-sm font-medium text-gray-800 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400">
                                        Product Launch
                                    </h4>
                                </div>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <div class="flex items-center gap-1">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">June 2, 2025</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                            </path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Town Square</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z">
                                            </path>
                                        </svg>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">350 attendees</span>
                                    </div>
                                </div>
                            </div>
                            <button
                                class="ml-2 rounded-full p-1 text-gray-400 opacity-0 transition-opacity hover:bg-gray-100 hover:text-gray-600 group-hover:opacity-100 dark:hover:bg-gray-800 dark:hover:text-gray-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z">
                                    </path>
                                </svg>
                            </button>
                        </div>

                        <!-- Annual Charity Gala -->
                        <div
                            class="group flex items-center py-3 transition-all hover:bg-pink-50/30 dark:hover:bg-pink-900/10 rounded-lg">
                            <div class="mr-4 flex-shrink-0 relative">
                                <div
                                    class="rounded-xl bg-pink-100 p-2.5 text-pink-600 ring-4 ring-pink-50/50 dark:bg-pink-900/50 dark:text-pink-300 dark:ring-pink-900/20">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div
                                    class="absolute -bottom-1 -right-1 h-4 w-4 rounded-full border-2 border-white bg-green-500 dark:border-gray-800">
                                </div>
                            </div>
                            <div class="flex-grow">
                                <div class="flex items-center justify-between">
                                    <h4
                                        class="text-sm font-medium text-gray-800 group-hover:text-pink-600 dark:text-white dark:group-hover:text-pink-400">
                                        Annual Charity Gala
                                    </h4>
                                </div>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <div class="flex items-center gap-1">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">June 15, 2025</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                            </path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">City Ballroom</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z">
                                            </path>
                                        </svg>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">500 attendees</span>
                                    </div>
                                </div>
                            </div>
                            <button
                                class="ml-2 rounded-full p-1 text-gray-400 opacity-0 transition-opacity hover:bg-gray-100 hover:text-gray-600 group-hover:opacity-100 dark:hover:bg-gray-800 dark:hover:text-gray-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-center border-t border-neutral-200 bg-neutral-50 px-6 py-2 dark:border-neutral-700 dark:bg-neutral-800/50">
                        {{-- <button
                            class="text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                            View All Events
                        </button> --}}
                    </div>
                </div>
                <div class="rounded-2xl border p-5 shadow-md hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Petty Cash</h3>
                        </div>
                        <div class="inline-flex overflow-hidden rounded-lg bg-gray-100 p-0.5 dark:bg-gray-700">
                            <button
                                class="px-2 py-1 text-xs font-medium rounded-md transition-colors hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300">
                                Week
                            </button>
                            <button
                                class="px-2 py-1 text-xs font-medium rounded-md transition-colors bg-emerald-600 text-white hover:bg-emerald-700">
                                Month
                            </button>
                            <button
                                class="px-2 py-1 text-xs font-medium rounded-md transition-colors hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300">
                                Year
                            </button>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                        <div class="rounded-lg bg-white/60 p-3 shadow-sm dark:bg-neutral-800/60">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Balance</p>
                            <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white">PKR 3,450</p>
                            <p class="mt-1 text-xs text-yellow-600 dark:text-yellow-400">-12.5% this month</p>
                        </div>
                        <div class="rounded-lg bg-white/60 p-3 shadow-sm dark:bg-neutral-800/60">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Recent Transactions</p>
                            <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white">24</p>
                            <p class="mt-1 text-xs text-green-600 dark:text-green-400">+8 since last week</p>
                        </div>
                        <div class="rounded-lg bg-white/60 p-3 shadow-sm dark:bg-neutral-800/60">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Pending Approvals</p>
                            <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white">5</p>
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">3 urgent</p>
                        </div>
                    </div>
                    <!-- Petty Cash Usage Breakdown -->
                    <div class="mt-4">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400">Petty Cash Usage Breakdown
                            </h4>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Last 30 days</span>
                        </div>
                        <!-- Progress bars for different expense categories -->
                        <div class="space-y-2">
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">Office Supplies</span>
                                    <span class="text-gray-800 dark:text-gray-300">PKR 950 (40%)</span>
                                </div>
                                <div class="h-1.5 w-full bg-gray-200 rounded-full dark:bg-gray-700">
                                    <div class="h-1.5 rounded-full bg-blue-500 dark:bg-blue-400" style="width: 40%">
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">Fuel</span>
                                    <span class="text-gray-800 dark:text-gray-300">PKR 780 (32%)</span>
                                </div>
                                <div class="h-1.5 w-full bg-gray-200 rounded-full dark:bg-gray-700">
                                    <div class="h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"
                                        style="width: 32%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">Refreshments</span>
                                    <span class="text-gray-800 dark:text-gray-300">PKR 420 (18%)</span>
                                </div>
                                <div class="h-1.5 w-full bg-gray-200 rounded-full dark:bg-gray-700">
                                    <div class="h-1.5 rounded-full bg-amber-500 dark:bg-amber-400" style="width: 18%">
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">Miscellaneous</span>
                                    <span class="text-gray-800 dark:text-gray-300">PKR 240 (10%)</span>
                                </div>
                                <div class="h-1.5 w-full bg-gray-200 rounded-full dark:bg-gray-700">
                                    <div class="h-1.5 rounded-full bg-purple-500 dark:bg-purple-400" style="width: 10%">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Analytics Charts Section - New addition -->
                <div class="lg:col-span-8 space-y-6">
                    <!-- Revenue Trends Chart -->
                    <div
                        class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-md dark:border-neutral-700 dark:bg-neutral-800">
                        <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Revenue Trends</h3>
                                <div class="flex space-x-2">
                                    <button
                                        class="px-3 py-1 text-xs rounded-md bg-neutral-100 text-neutral-700 hover:bg-neutral-200 dark:bg-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-600">
                                        Monthly
                                    </button>
                                    <button
                                        class="px-3 py-1 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                        Quarterly
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="p-5">
                            <!-- SVG Chart Visualization -->
                            <div class="relative h-60">
                                <!-- Grid lines -->
                                <div class="absolute inset-0 grid grid-cols-4 grid-rows-4">
                                    <div class="border-b border-r border-gray-200 dark:border-gray-700"></div>
                                    <div class="border-b border-r border-gray-200 dark:border-gray-700"></div>
                                    <div class="border-b border-r border-gray-200 dark:border-gray-700"></div>
                                    <div class="border-b border-gray-200 dark:border-gray-700"></div>
                                    <div class="border-b border-r border-gray-200 dark:border-gray-700"></div>
                                    <div class="border-b border-r border-gray-200 dark:border-gray-700"></div>
                                    <div class="border-b border-r border-gray-200 dark:border-gray-700"></div>
                                    <div class="border-b border-gray-200 dark:border-gray-700"></div>
                                    <div class="border-b border-r border-gray-200 dark:border-gray-700"></div>
                                    <div class="border-b border-r border-gray-200 dark:border-gray-700"></div>
                                    <div class="border-b border-r border-gray-200 dark:border-gray-700"></div>
                                    <div class="border-b border-gray-200 dark:border-gray-700"></div>
                                    <div class="border-r border-gray-200 dark:border-gray-700"></div>
                                    <div class="border-r border-gray-200 dark:border-gray-700"></div>
                                    <div class="border-r border-gray-200 dark:border-gray-700"></div>
                                    <div></div>
                                </div>

                                <!-- Line chart for revenue -->
                                <svg class="absolute inset-0 h-full w-full" viewBox="0 0 400 200"
                                    preserveAspectRatio="none">
                                    <!-- Main trend line -->
                                    <path
                                        d="M0,160 L25,140 L50,150 L75,120 L100,130 L125,80 L150,100 L175,70 L200,80 L225,60 L250,50 L275,40 L300,20 L325,40 L350,30 L375,10 L400,20"
                                        fill="none" stroke="#3b82f6" stroke-width="3" stroke-linejoin="round"
                                        class="dark:stroke-blue-400" />

                                    <!-- Gradient area under the line -->
                                    <path
                                        d="M0,160 L25,140 L50,150 L75,120 L100,130 L125,80 L150,100 L175,70 L200,80 L225,60 L250,50 L275,40 L300,20 L325,40 L350,30 L375,10 L400,20 L400,200 L0,200 Z"
                                        fill="url(#blue-gradient)" fill-opacity="0.2" />

                                    <!-- Gradient definition -->
                                    <defs>
                                        <linearGradient id="blue-gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                            <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.5"
                                                class="dark:stop-color-blue-400" />
                                            <stop offset="100%" stop-color="#3b82f6" stop-opacity="0"
                                                class="dark:stop-color-blue-900" />
                                        </linearGradient>
                                    </defs>

                                    <!-- Data points -->
                                    <circle cx="0" cy="160" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="25" cy="140" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="50" cy="150" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="75" cy="120" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="100" cy="130" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="125" cy="80" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="150" cy="100" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="175" cy="70" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="200" cy="80" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="225" cy="60" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="250" cy="50" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="275" cy="40" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="300" cy="20" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="325" cy="40" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="350" cy="30" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="375" cy="10" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                    <circle cx="400" cy="20" r="4" fill="#3b82f6" class="dark:fill-blue-400" />
                                </svg>
                            </div>

                            <!-- X-axis labels -->
                            <div class="mt-2 flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                <span>Q1</span>
                                <span>Q2</span>
                                <span>Q3</span>
                                <span>Q4</span>
                            </div>

                            <!-- Legend -->
                            <div class="mt-4 flex items-center">
                                <div class="h-3 w-3 rounded bg-blue-500 dark:bg-blue-400"></div>
                                <span class="ml-2 text-xs text-gray-600 dark:text-gray-300">Revenue (PKR )</span>
                            </div>
                        </div>
                    </div>

                    <!-- Attendee Distribution By Category Chart -->
                    {{-- <div
                        class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-md dark:border-neutral-700 dark:bg-neutral-800">
                        <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Attendee Distribution
                                </h3>
                                <div class="flex space-x-2">
                                    <button
                                        class="px-3 py-1 text-xs rounded-md bg-neutral-100 text-neutral-700 hover:bg-neutral-200 dark:bg-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-600">
                                        Age
                                    </button>
                                    <button
                                        class="px-3 py-1 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                        Category
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="p-5">
                            <!-- SVG Chart Visualization (Bar chart) -->
                            <div class="h-60 relative">
                                <!-- Y-axis labels -->
                                <div
                                    class="absolute left-0 inset-y-0 flex flex-col justify-between text-xs text-gray-500 dark:text-gray-400 w-8">
                                    <span>500</span>
                                    <span>400</span>
                                    <span>300</span>
                                    <span>200</span>
                                    <span>100</span>
                                    <span>0</span>
                                </div>

                                <!-- Bars container -->
                                <div class="ml-10 flex h-full items-end justify-around space-x-8">
                                    <!-- Corporate Bar -->
                                    <div class="flex flex-col items-center">
                                        <div class="relative w-12">
                                            <div class="absolute bottom-0 w-full rounded-t-lg bg-blue-500 dark:bg-blue-400 transition-all hover:bg-blue-600 dark:hover:bg-blue-300 group"
                                                style="height: 70%;">
                                                <div
                                                    class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs font-medium text-white opacity-0 group-hover:opacity-100 dark:bg-gray-700">
                                                    350 attendees
                                                </div>
                                            </div>
                                        </div>
                                        <span class="mt-2 text-xs text-gray-600 dark:text-gray-400">Corp.</span>
                                    </div>

                                    <!-- Academic Bar -->
                                    <div class="flex flex-col items-center">
                                        <div class="relative w-12">
                                            <div class="absolute bottom-0 w-full rounded-t-lg bg-purple-500 dark:bg-purple-400 transition-all hover:bg-purple-600 dark:hover:bg-purple-300 group"
                                                style="height: 50%;">
                                                <div
                                                    class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs font-medium text-white opacity-0 group-hover:opacity-100 dark:bg-gray-700">
                                                    250 attendees
                                                </div>
                                            </div>
                                        </div>
                                        <span class="mt-2 text-xs text-gray-600 dark:text-gray-400">Acad.</span>
                                    </div>

                                    <!-- Professional Bar -->
                                    <div class="flex flex-col items-center">
                                        <div class="relative w-12">
                                            <div class="absolute bottom-0 w-full rounded-t-lg bg-green-500 dark:bg-green-400 transition-all hover:bg-green-600 dark:hover:bg-green-300 group"
                                                style="height: 90%;">
                                                <div
                                                    class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs font-medium text-white opacity-0 group-hover:opacity-100 dark:bg-gray-700">
                                                    450 attendees
                                                </div>
                                            </div>
                                        </div>
                                        <span class="mt-2 text-xs text-gray-600 dark:text-gray-400">Prof.</span>
                                    </div>

                                    <!-- Student Bar -->
                                    <div class="flex flex-col items-center">
                                        <div class="relative w-12">
                                            <div class="absolute bottom-0 w-full rounded-t-lg bg-yellow-500 dark:bg-yellow-400 transition-all hover:bg-yellow-600 dark:hover:bg-yellow-300 group"
                                                style="height: 40%;">
                                                <div
                                                    class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs font-medium text-white opacity-0 group-hover:opacity-100 dark:bg-gray-700">
                                                    200 attendees
                                                </div>
                                            </div>
                                        </div>
                                        <span class="mt-2 text-xs text-gray-600 dark:text-gray-400">Student</span>
                                    </div>

                                    <!-- Other Bar -->
                                    <div class="flex flex-col items-center">
                                        <div class="relative w-12">
                                            <div class="absolute bottom-0 w-full rounded-t-lg bg-pink-500 dark:bg-pink-400 transition-all hover:bg-pink-600 dark:hover:bg-pink-300 group"
                                                style="height: 30%;">
                                                <div
                                                    class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs font-medium text-white opacity-0 group-hover:opacity-100 dark:bg-gray-700">
                                                    150 attendees
                                                </div>
                                            </div>
                                        </div>
                                        <span class="mt-2 text-xs text-gray-600 dark:text-gray-400">Other</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Legend -->
                            <div class="mt-4 grid grid-cols-3 gap-2">
                                <div class="flex items-center">
                                    <div class="h-3 w-3 rounded bg-blue-500 dark:bg-blue-400"></div>
                                    <span class="ml-2 text-xs text-gray-600 dark:text-gray-300">Corporate</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="h-3 w-3 rounded bg-purple-500 dark:bg-purple-400"></div>
                                    <span class="ml-2 text-xs text-gray-600 dark:text-gray-300">Academic</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="h-3 w-3 rounded bg-green-500 dark:bg-green-400"></div>
                                    <span class="ml-2 text-xs text-gray-600 dark:text-gray-300">Professional</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="h-3 w-3 rounded bg-yellow-500 dark:bg-yellow-400"></div>
                                    <span class="ml-2 text-xs text-gray-600 dark:text-gray-300">Student</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="h-3 w-3 rounded bg-pink-500 dark:bg-pink-400"></div>
                                    <span class="ml-2 text-xs text-gray-600 dark:text-gray-300">Other</span>
                                </div>
                            </div>
                        </div>
                    </div> --}}
                </div>

                <!-- Right Column: Stats and Charts -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Event Closure Alerts -->
                    <div
                        class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-md dark:border-neutral-700 dark:bg-neutral-800">
                        <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Closing Events</h3>
                                <span
                                    class="rounded-lg bg-neutral-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-neutral-700 dark:text-gray-300">
                                    6 Active
                                </span>
                            </div>
                        </div>
                        <div
                            class="flex max-h-[240px] flex-col overflow-y-auto divide-y divide-neutral-100 dark:divide-neutral-700/50">
                            <!-- Alert Item 1 - Urgent/Critical -->
                            <div class="group hover:bg-neutral-50 dark:hover:bg-neutral-800/70">
                                <div
                                    class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 dark:border-red-500">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                                            <h4
                                                class="text-sm font-semibold text-gray-800 dark:text-white group-hover:text-red-600 dark:group-hover:text-red-400">
                                                Product Launch</h4>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Zero Tech Solutions
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">May 17,
                                            2025</span>
                                        <span class="block text-sm font-medium text-red-600 dark:text-red-400">2 days
                                            11
                                            hours from now</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Alert Item 2 - Warning -->
                            <div class="group hover:bg-neutral-50 dark:hover:bg-neutral-800/70">
                                <div
                                    class="flex items-center justify-between p-4 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-400 dark:border-amber-400">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-2 w-2 rounded-full bg-amber-400"></span>
                                            <h4
                                                class="text-sm font-semibold text-gray-800 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400">
                                                Award Ceremony</h4>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Mobilink Telecom</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">May 20,
                                            2025</span>
                                        <span class="block text-sm font-medium text-amber-600 dark:text-amber-400">5
                                            days
                                            11 hours from now</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Alert Item 3 - Warning -->
                            <div class="group hover:bg-neutral-50 dark:hover:bg-neutral-800/70">
                                <div
                                    class="flex items-center justify-between p-4 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-400 dark:border-amber-400">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-2 w-2 rounded-full bg-amber-400"></span>
                                            <h4
                                                class="text-sm font-semibold text-gray-800 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400">
                                                Donation Gala</h4>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Alkhidmat Foundation
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">May 20,
                                            2025</span>
                                        <span class="block text-sm font-medium text-amber-600 dark:text-amber-400">5
                                            days
                                            11 hours from now</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Alert Item 4 - Normal -->
                            <div class="group hover:bg-neutral-50 dark:hover:bg-neutral-800/70">
                                <div
                                    class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/10 border-l-4 border-blue-300 dark:border-blue-500/40">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-2 w-2 rounded-full bg-blue-400"></span>
                                            <h4
                                                class="text-sm font-semibold text-gray-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                                Tech Summit</h4>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">NETSOL Technologies
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">May 25,
                                            2025</span>
                                        <span class="block text-sm font-medium text-gray-600 dark:text-gray-400">10
                                            days 11
                                            hours from now</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Alert Item 5 - Normal -->
                            <div class="group hover:bg-neutral-50 dark:hover:bg-neutral-800/70">
                                <div
                                    class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/10 border-l-4 border-blue-300 dark:border-blue-500/40">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-2 w-2 rounded-full bg-blue-400"></span>
                                            <h4
                                                class="text-sm font-semibold text-gray-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                                Family Festival</h4>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Wapda Corporation</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">May 25,
                                            2025</span>
                                        <span class="block text-sm font-medium text-gray-600 dark:text-gray-400">10
                                            days 11
                                            hours from now</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Event Type Distribution -->

                    <div
                        class="overflow-hidden rounded-xl border border-red-100/50 bg-gradient-to-r from-white via-red-50/10 to-white shadow-lg transition-all hover:shadow-xl dark:border-red-900/30 dark:from-neutral-800 dark:via-red-900/10 dark:to-neutral-800">
                        <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-700">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Event Types</h3>
                        </div>
                        <div class="divide-y divide-neutral-100 dark:divide-neutral-700/50 px-5">
                            <div class="flex items-center py-3 hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                    <div class="h-3 w-3 rounded-full bg-blue-500"></div>
                                </div>
                                <span class="ml-3 flex-1 text-sm font-medium text-gray-600 dark:text-gray-400">Corporate
                                    Conferences</span>
                                <span class="text-sm font-medium text-gray-800 dark:text-white">38%</span>
                            </div>

                            <div class="flex items-center py-3 hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                                    <div class="h-3 w-3 rounded-full bg-green-500"></div>
                                </div>
                                <span class="ml-3 flex-1 text-sm font-medium text-gray-600 dark:text-gray-400">Workshops
                                    &
                                    Training</span>
                                <span class="text-sm font-medium text-gray-800 dark:text-white">15%</span>
                            </div>

                            <div class="flex items-center py-3 hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400">
                                    <div class="h-3 w-3 rounded-full bg-yellow-500"></div>
                                </div>
                                <span class="ml-3 flex-1 text-sm font-medium text-gray-600 dark:text-gray-400">Social
                                    Events</span>
                                <span class="text-sm font-medium text-gray-800 dark:text-white">12%</span>
                            </div>

                            <div class="flex items-center py-3 hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-pink-100 text-pink-600 dark:bg-pink-900/30 dark:text-pink-400">
                                    <div class="h-3 w-3 rounded-full bg-pink-500"></div>
                                </div>
                                <span class="ml-3 flex-1 text-sm font-medium text-gray-600 dark:text-gray-400">Other
                                    Events</span>
                                <span class="text-sm font-medium text-gray-800 dark:text-white">8%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -  Interactive Buttons -->
                    {{-- <div
                        class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-md dark:border-neutral-700 dark:bg-neutral-800">
                        <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-700">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Quick Actions</h3>
                        </div>
                        <div class="p-5 space-y-3">
                            <button
                                class="group flex w-full items-center justify-between rounded-lg bg-gradient-to-r from-indigo-50 to-indigo-100 px-4 py-3 text-sm font-medium text-indigo-700 shadow-sm transition-all hover:from-indigo-100 hover:to-indigo-200 dark:from-indigo-900/30 dark:to-indigo-800/30 dark:text-indigo-300 dark:hover:from-indigo-800/40 dark:hover:to-indigo-700/40">
                                <div class="flex items-center">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400">
                                        <svg class="h-4 w-4 transition-transform group-hover:scale-110" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                    </div>
                                    <span class="ml-3">Create New Event</span>
                                </div>
                                <svg class="h-5 w-5 text-indigo-600 transition-transform group-hover:translate-x-1 dark:text-indigo-400"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>

                            <button
                                class="group flex w-full items-center justify-between rounded-lg bg-gradient-to-r from-emerald-50 to-emerald-100 px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm transition-all hover:from-emerald-100 hover:to-emerald-200 dark:from-emerald-900/30 dark:to-emerald-800/30 dark:text-emerald-300 dark:hover:from-emerald-800/40 dark:hover:to-emerald-700/40">
                                <div class="flex items-center">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                                        <svg class="h-4 w-4 transition-transform group-hover:scale-110" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <span class="ml-3">Approve Pending Events</span>
                                </div>
                                <span
                                    class="rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-neutral-700 dark:text-emerald-300">3</span>
                            </button>

                            <button
                                class="group flex w-full items-center justify-between rounded-lg bg-gradient-to-r from-purple-50 to-purple-100 px-4 py-3 text-sm font-medium text-purple-700 shadow-sm transition-all hover:from-purple-100 hover:to-purple-200 dark:from-purple-900/30 dark:to-purple-800/30 dark:text-purple-300 dark:hover:from-purple-800/40 dark:hover:to-purple-700/40">
                                <div class="flex items-center">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400">
                                        <svg class="h-4 w-4 transition-transform group-hover:scale-110" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <span class="ml-3">Generate Reports</span>
                                </div>
                                <svg class="h-5 w-5 text-purple-600 transition-transform group-hover:translate-x-1 dark:text-purple-400"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div> --}}

                </div>
            </div>


        </div>
</x-layouts.app>