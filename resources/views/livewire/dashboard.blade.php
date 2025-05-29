<?php

use Livewire\Volt\Component;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\JobBooking;
use Carbon\Carbon;

new class extends Component {
    public $selectedOrganizationId = null;
    public $organizations = [];
    public $stats = [
        'upcomingEvents' => 0,
        'totalRevenue' => 0,
        'totalExpenses' => 0,
        'venues' => 0,
    ];
    public $upcomingEvents = [];
    public $currentDate;

    public function mount()
    {
        // Get all organizations
        $this->organizations = Organization::all();

        // Set default selected organization if available
        if ($this->organizations->count() > 0) {
            $this->selectedOrganizationId = $this->organizations->first()->id;
        }

        // Set current date
        $this->currentDate = Carbon::now()->format('F j, Y');

        // Load initial stats
        $this->loadOrganizationStats();
    }

    public function updatedSelectedOrganizationId()
    {
        $this->loadOrganizationStats();
    }

    public function loadOrganizationStats()
    {
        if (!$this->selectedOrganizationId) {
            return;
        }

        // Load organization-specific analytics
        $organization = Organization::find($this->selectedOrganizationId);

        if (!$organization) {
            return;
        }

        // Get upcoming events (jobs) for this organization
        $upcomingJobs = JobBooking::where('organization_id', $this->selectedOrganizationId)->where('status', 'open')->orderBy('created_at', 'desc')->take(4)->get();

        $this->upcomingEvents = $upcomingJobs->map(function ($job) {
            return [
                'title' => $job->campaign,
                'date' => Carbon::parse($job->created_at)->addDays(rand(3, 30))->format('M d, Y'),
                'venue' => ['Convention Center', 'Grand Hotel', 'Town Square', 'Exhibition Hall'][rand(0, 3)],
                'attendees' => rand(100, 500),
                'status' => rand(0, 1) ? 'green' : 'yellow', // For the status indicator
            ];
        });

        // Calculate stats
        $this->stats = [
            'upcomingEvents' => JobBooking::where('organization_id', $this->selectedOrganizationId)->where('status', 'open')->count(),

            'totalRevenue' => Transaction::where('to_organization_id', $this->selectedOrganizationId)
                ->whereIn('transaction_type', ['invoice', 'payment', 'fund', 'revenue'])
                ->sum('amount'),

            'totalExpenses' => Transaction::where('from_organization_id', $this->selectedOrganizationId)
                ->whereIn('transaction_type', ['expense'])
                ->sum('amount'),

            'venues' => rand(5, 10), // Placeholder for demo purposes
        ];
    }
}; ?>

<div>
    <!-- Header with welcome message and date -->
    <div
        class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-xl bg-white p-6 shadow-md dark:bg-zinc-800">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white sm:text-3xl">
                {{-- name of selected organization --}}
                {{ $selectedOrganizationId && count($organizations) > 0 ? $organizations->firstWhere('id',
                $selectedOrganizationId)->name : 'Select Organization' }}
            </h1>
        </div>

        <!-- Enhanced Organization Selector Dropdown -->
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <div class="flex items-center">
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button"
                        class="flex items-center justify-between gap-2 rounded-lg border border-indigo-300 bg-gradient-to-r from-indigo-50 to-white px-4 py-2.5
                                   text-sm font-medium text-indigo-700 shadow-sm transition-all hover:border-indigo-400
                                   hover:from-indigo-100 hover:to-white hover:shadow
                                   dark:border-indigo-700 dark:from-indigo-900/40 dark:to-indigo-800/20 dark:text-indigo-300
                                   dark:hover:border-indigo-600 dark:hover:from-indigo-800/30 dark:hover:to-indigo-700/20">
                        <span class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span>{{ $selectedOrganizationId && count($organizations) > 0 ?
                                $organizations->firstWhere('id', $selectedOrganizationId)->name : 'Select Organization'
                                }}</span>
                        </span>
                        <svg class="h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-show="open" @click.outside="open = false"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 z-10 mt-2 w-48 overflow-hidden rounded-lg border border-indigo-200 bg-white shadow-lg
                                dark:border-indigo-800 dark:bg-zinc-800" style="display: none;">
                        <div class="max-h-60 overflow-y-auto py-1">
                            @foreach ($organizations as $organization)
                            <button type="button" wire:click="$set('selectedOrganizationId', '{{ $organization->id }}')"
                                @click="open = false" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm hover:bg-indigo-50 hover:text-indigo-700
                                               {{ $selectedOrganizationId == $organization->id ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300' }}
                                               dark:hover:bg-indigo-900/30 dark:hover:text-indigo-300">
                                <div
                                    class="flex-shrink-0 h-6 w-6 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-800 font-semibold text-xs dark:bg-indigo-900 dark:text-indigo-200">
                                    {{ substr($organization->name, 0, 1) }}
                                </div>
                                <span>{{ $organization->name }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="hidden sm:inline-block h-6 w-px bg-gray-300 dark:bg-gray-600"></span>
                <p class="text-sm text-gray-600 dark:text-gray-400">Today is</p>
                <h2 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white">{{ $currentDate }}</h2>
            </div>
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
                        <span class="text-3xl font-bold text-gray-800 dark:text-white">{{ $stats['upcomingEvents']
                            }}</span>
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
                        <span class="text-3xl font-bold text-gray-800 dark:text-white">PKR
                            {{ number_format($stats['totalRevenue']) }}</span>
                        <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">This Month</span>
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
                        <span class="ml-2 text-gray-500 dark:text-gray-400">from last month</span>
                    </div>
                </div>
            </div>

            <!-- Total Expenses Card -->
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
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white">Total Expenses Booked</h3>
                    <div class="flex items-baseline">
                        <span class="text-3xl font-bold text-gray-800 dark:text-white">PKR
                            {{ number_format($stats['totalExpenses']) }}</span>
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
                        <span class="text-3xl font-bold text-gray-800 dark:text-white">{{ $stats['venues'] }}</span>
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

                    <!-- Events Timeline - Enhanced with dynamic data -->
                    <div
                        class="divide-y divide-neutral-100 dark:divide-neutral-700/50 px-6 py-2 max-h-[240px] overflow-y-auto">
                        @if (count($upcomingEvents) > 0)
                        @foreach ($upcomingEvents as $event)
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
                                    class="absolute -bottom-1 -right-1 h-4 w-4 rounded-full border-2 border-white bg-{{ $event['status'] }}-500 dark:border-gray-800">
                                </div>
                            </div>
                            <div class="flex-grow">
                                <div class="flex items-center justify-between">
                                    <h4
                                        class="text-sm font-medium text-gray-800 group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">
                                        {{ $event['title'] }}
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
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $event['date']
                                            }}</span>
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
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $event['venue']
                                            }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z">
                                            </path>
                                        </svg>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $event['attendees'] }}
                                            attendees</span>
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
                        @endforeach
                        @else
                        <div class="flex items-center justify-center py-8 text-center">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium">No upcoming events</h3>
                                <p class="mt-1 text-sm">Start by creating a new event or job booking.</p>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div
                        class="flex items-center justify-center border-t border-neutral-200 bg-neutral-50 px-6 py-2 dark:border-neutral-700 dark:bg-neutral-800/50">
                        <a href="{{ route('sales.job-booking') }}"
                            class="text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                            View All Events
                        </a>
                    </div>
                </div>
                <div class="rounded-2xl border p-5 shadow-md hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Petty Cash</h3>
                            @if ($selectedOrganizationId && count($organizations) > 0)
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $organizations->where('id', $selectedOrganizationId)->first()->name }}
                            </p>
                            @endif
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
                                    <span class="font-medium text-gray-800 dark:text-gray-200">PKR 1,200</span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden dark:bg-gray-700">
                                    <div class="bg-emerald-500 h-full rounded-full" style="width: 35%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">Travel Expenses</span>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">PKR 900</span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden dark:bg-gray-700">
                                    <div class="bg-indigo-500 h-full rounded-full" style="width: 26%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">Refreshments</span>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">PKR 750</span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden dark:bg-gray-700">
                                    <div class="bg-amber-500 h-full rounded-full" style="width: 22%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">Miscellaneous</span>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">PKR 600</span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden dark:bg-gray-700">
                                    <div class="bg-purple-500 h-full rounded-full" style="width: 17%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Analytics Charts Section -->
                <div class="lg:col-span-8 space-y-6">
                    <!-- Revenue Trends Chart -->
                    <div
                        class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-md dark:border-neutral-700 dark:bg-neutral-800">
                        <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-700">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-800 dark:text-white">Revenue Trends</h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Organization:</span>
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-200">
                                        {{ $selectedOrganizationId && count($organizations) > 0
                                        ? $organizations->where('id', $selectedOrganizationId)->first()->name
                                        : 'All' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="p-5 flex items-center justify-center h-64">
                            <div class="text-center text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium">Revenue chart will appear here</h3>
                                <p class="mt-1 text-sm">Select an organization to see detailed analytics.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary Section -->
                <div class="lg:col-span-4 space-y-6">
                    <div
                        class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-md dark:border-neutral-700 dark:bg-neutral-800">
                        <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-700">
                            <h3 class="font-semibold text-gray-800 dark:text-white">Financial Summary</h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Total Revenue</span>
                                <span class="font-medium text-gray-800 dark:text-white">PKR
                                    {{ number_format($stats['totalRevenue']) }}</span>
                            </div>
                            <div class="h-0.5 bg-gray-200 dark:bg-gray-700"></div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Total Expenses</span>
                                <span class="font-medium text-gray-800 dark:text-white">PKR
                                    {{ number_format($stats['totalExpenses']) }}</span>
                            </div>
                            <div class="h-0.5 bg-gray-200 dark:bg-gray-700"></div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-800 dark:text-white">Net Profit</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">PKR
                                    {{ number_format($stats['totalRevenue'] - $stats['totalExpenses']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>