<?php

use Livewire\Volt\Component;
use App\Models\JobBooking;
use App\Models\JobCosting;
use App\Models\Client;
use App\Models\Vendor;
use App\Models\Organization;
use App\Models\Item;
use Livewire\WithPagination;
use Illuminate\Support\Collection;

new class extends Component {
    use WithPagination;

    public $selectedClientId = '';
    public $selectedJobBookingId = '';
    public $clients = [];
    public $jobBookings = [];
    public $costingItems = [];

    // For the costing table
    public $actualRates = [];
    public $notes = [];

    public function mount()
    {
        $this->clients = Client::all();
        $this->loadJobBookings();
    }

    public function loadJobBookings()
    {
        if ($this->selectedClientId) {
            $this->jobBookings = JobBooking::with(['client', 'organization'])
                ->where('client_id', $this->selectedClientId)
                ->get();
        } else {
            $this->jobBookings = collect();
        }

        // Reset job booking selection when client changes
        $this->selectedJobBookingId = '';
        $this->costingItems = [];
    }

    public function loadCostingItems()
    {
        if ($this->selectedJobBookingId) {
            $jobBooking = JobBooking::with(['jobCostings.vendor', 'jobCostings.item'])->find($this->selectedJobBookingId);

            if ($jobBooking) {
                $this->costingItems = $jobBooking->jobCostings
                    ->map(function ($costing) {
                        // Initialize actual rates and notes arrays
                        $this->actualRates[$costing->id] = $costing->actual_rate ?? $costing->rate;
                        $this->notes[$costing->id] = $costing->notes ?? '';

                        $actualRate = $costing->actual_rate ?? $costing->rate;
                        $actualAmount = $costing->quantity * $actualRate;
                        $difference = $actualAmount - $costing->total_amount;
                        $diffPercentage = $costing->total_amount > 0 ? round(($difference / $costing->total_amount) * 100, 2) : 0;

                        return [
                            'id' => $costing->id,
                            'sub_item_name' => $costing->subAccount->name ?? 'N/A',
                            'sub_account_name' => $costing->subItem->name ?? 'N/A',
                            'vendor_name' => $costing->vendor->name ?? 'N/A',
                            'quantity' => $costing->quantity,
                            'estimated_rate' => $costing->rate,
                            'estimated_amount' => $costing->total_amount,
                            'actual_rate' => $actualRate,
                            'actual_amount' => $actualAmount,
                            'difference' => $difference,
                            'difference_percentage' => $diffPercentage,
                        ];
                    })
                    ->toArray();
            }
        }
    }

    public function updatedSelectedClientId()
    {
        $this->loadJobBookings();
    }

    public function updatedSelectedJobBookingId()
    {
        $this->loadCostingItems();
    }

    public function updatedActualRates($value, $key)
    {
        $this->calculateDifferences();
    }

    public function calculateDifferences()
    {
        foreach ($this->costingItems as $index => $item) {
            $costingId = $item['id'];
            $actualRate = floatval($this->actualRates[$costingId] ?? 0);
            $actualAmount = $item['quantity'] * $actualRate;
            $estimatedAmount = $item['estimated_amount'];

            $difference = $actualAmount - $estimatedAmount;
            $diffPercentage = $estimatedAmount > 0 ? round(($difference / $estimatedAmount) * 100, 2) : 0;

            $this->costingItems[$index]['actual_rate'] = $actualRate;
            $this->costingItems[$index]['actual_amount'] = $actualAmount;
            $this->costingItems[$index]['difference'] = $difference;
            $this->costingItems[$index]['difference_percentage'] = $diffPercentage;
        }
    }

    public function saveItem($costingId)
    {
        $costing = JobCosting::find($costingId);
        if ($costing) {
            $costing->update([
                'actual_rate' => $this->actualRates[$costingId] ?? $costing->rate,
                'notes' => $this->notes[$costingId] ?? null,
            ]);

            session()->flash('message', 'Item updated successfully');
        }
    }

    public function saveAllChanges()
    {
        foreach ($this->actualRates as $costingId => $actualRate) {
            $this->saveItem($costingId);
        }

        session()->flash('message', 'All changes saved successfully');
    }

    public function getTotalEstimated()
    {
        return collect($this->costingItems)->sum('estimated_amount');
    }

    public function getTotalActual()
    {
        return collect($this->costingItems)->sum('actual_amount');
    }

    public function getTotalDifference()
    {
        return $this->getTotalActual() - $this->getTotalEstimated();
    }

    public function getTotalDifferencePercentage()
    {
        $estimated = $this->getTotalEstimated();
        return $estimated > 0 ? round(($this->getTotalDifference() / $estimated) * 100, 2) : 0;
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <div class="flex items-center space-x-2">
                    <flux:icon name="calculator" class="h-10 w-10" />
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-slate-100">Actual Costing
                    </h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Compare estimated vs actual costs for job bookings
                </p>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Export or other actions can go here -->
            </div>
        </div>

        <!-- Selection Section -->
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm mb-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">
                    <h3 class="text-lg font-medium text-indigo-100">Select Job for Costing</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Select Client -->
                <div>
                    <label class="block text-sm font-medium text-indigo-200 mb-2">Select Client</label>
                    <flux:select wire:model.live="selectedClientId" class="w-full">
                        <flux:select.option value="">-- Select Client --</flux:select.option>
                        @foreach ($clients as $client)
                        <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Select Job Booking -->
                <div>
                    <label class="block text-sm font-medium text-indigo-200 mb-2">Select Job Booking</label>
                    <flux:select wire:model.live="selectedJobBookingId" class="w-full" :disabled="!$selectedClientId">
                        <flux:select.option value="">-- Select Job Booking --</flux:select.option>
                        @foreach ($jobBookings as $jobBooking)
                        <flux:select.option value="{{ $jobBooking->id }}">{{ $jobBooking->job_number }} - {{
                            $jobBooking->campaign }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </x-glass-card>

        <!-- Costing Table -->
        @if ($selectedJobBookingId && !empty($costingItems))
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">
                    <h3 class="text-lg font-medium text-indigo-100">Job Costing Details</h3>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-indigo-300 mr-2">{{ count($costingItems) }} {{ Str::plural('item',
                        count($costingItems)) }}</span>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg">
                <table class="min-w-full divide-y divide-indigo-200/20">
                    <!-- Header Section with Gradient -->
                    <thead class="bg-gradient-to-r from-indigo-900/40 to-indigo-800/30 backdrop-blur-sm">
                        <tr>
                            {{-- <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Item</span>
                                </div>
                            </th> --}}
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Vendor</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center justify-center space-x-1">
                                    <span>Qty</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-8 py-6 text-center text-xs font-semibold uppercase tracking-wider text-indigo-100 border-l-2 border-r-2 border-blue-400/50 bg-blue-900/30 min-w-[280px]">
                                <div class="text-blue-300 text-sm font-bold mb-3">Estimated</div>
                                <div
                                    class="grid grid-cols-2 gap-4 text-xs font-normal border-t border-blue-400/30 pt-3">
                                    <span class="border-r border-blue-400/30 pr-4">Rate</span>
                                    <span class="pl-4">Amount</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-8 py-6 text-center text-xs font-semibold uppercase tracking-wider text-indigo-100 border-l-2 border-r-2 border-purple-400/50 bg-purple-900/30 min-w-[280px]">
                                <div class="text-purple-300 text-sm font-bold mb-3">Actual</div>
                                <div
                                    class="grid grid-cols-2 gap-4 text-xs font-normal border-t border-purple-400/30 pt-3">
                                    <span class="border-r border-purple-400/30 pr-4">Rate</span>
                                    <span class="pl-4">Amount</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center justify-center space-x-1">
                                    <span>Difference</span>
                                </div>
                            </th>
                            {{-- <th scope="col"
                                class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center justify-center space-x-1">
                                    <span>Notes</span>
                                </div>
                            </th> --}}
                            <th scope="col"
                                class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <span>Actions</span>
                            </th>
                        </tr>
                    </thead>

                    <!-- Table Body -->
                    <tbody class="bg-indigo-900/10 backdrop-blur-sm divide-y divide-indigo-200/10">
                        @foreach ($costingItems as $index => $item)
                        <tr class="hover:bg-indigo-900/20 transition-colors duration-200">
                            <!-- Item Name -->
                            {{-- <td class="px-6 py-4 text-sm">
                                <div class="font-medium text-indigo-100">{{ $item['sub_item_name'] }}
                                    @if (!empty($item['sub_account_name']))
                                    <div class="text-xs text-indigo-300 mt-1">{{ $item['sub_account_name'] }}</div>
                                    @endif
                                </div>
                            </td> --}}

                            <!-- Vendor Name -->
                            <td class="px-6 py-4 text-sm text-indigo-200">
                                {{ $item['vendor_name'] }}
                            </td>

                            <!-- Quantity -->
                            <td class="px-6 py-4 text-sm text-center">
                                <div class="font-semibold text-indigo-100">{{ $item['quantity'] }}</div>
                            </td>

                            <!-- Estimated -->
                            <td
                                class="px-8 py-6 text-sm text-center border-l-2 border-r-2 border-blue-400/30 bg-blue-900/10">
                                <div class="grid grid-cols-2 gap-4 items-center">
                                    <div class="text-blue-300 font-medium border-r border-blue-400/30 pr-4 text-right">
                                        <div class="text-xs text-blue-400 mb-1"></div>
                                        <div class="text-lg font-bold">{{ number_format($item['estimated_rate'],2) }}
                                        </div>
                                    </div>
                                    <div class="text-blue-200 font-semibold pl-0 text-left">
                                        <div class="text-xs text-blue-400 mb-1"></div>
                                        <div class="text-lg font-bold">{{ number_format($item['estimated_amount'],2) }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Actual -->
                            <td
                                class="px-8 py-6 text-sm text-center border-l-2 border-r-2 border-purple-400/30 bg-purple-900/10">
                                <div class="grid grid-cols-2 gap-4 items-center">
                                    <div class="relative border-r border-purple-400/30 pr-4">
                                        <div class="text-xs text-purple-400 mb-2"></div>
                                        <flux:input type="number" step="0.01"
                                            wire:model.live="actualRates.{{ $item['id'] }}"
                                            class="w-full text-center text-purple-300 focus:border-purple-400 focus:ring-purple-400 text-sm font-bold py-2" />
                                    </div>
                                    <div class="text-purple-200 font-semibold pl-0 text-left">
                                        <div class="text-xs text-purple-400 mb-1"></div>
                                        <div class="text-lg font-bold">{{ number_format($item['actual_amount'],2) }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Difference -->
                            <td class="px-6 py-4 text-sm text-center">
                                <div class="space-y-1">
                                    <div
                                        class="font-bold {{ $item['difference'] < 0 ? 'text-red-400' : ($item['difference'] > 0 ? 'text-red-400' : 'text-green-400') }}">
                                        <div class="flex items-center justify-center space-x-1">
                                            <span>{{ $item['difference'] < 0 ? '' : '+' }}{{
                                                    number_format($item['difference'], 2) }}</span>
                                                    @if ($item['difference'] != 0)
                                                    @if ($item['difference']
                                                    < 0) <flux:icon name="arrow-down" class="h-3 w-3" />
                                                    @else
                                                    <flux:icon name="arrow-up" class="h-3 w-3" />
                                                    @endif
                                                    @endif
                                        </div>
                                    </div>
                                    <div
                                        class="text-xs {{ $item['difference'] < 0 ? 'text-red-300' : ($item['difference'] > 0 ? 'text-red-300' : 'text-green-300') }}">
                                        {{ $item['difference_percentage'] }}%
                                    </div>
                                </div>
                            </td>

                            <!-- Notes -->
                            {{-- <td class="px-6 py-4 text-sm">
                                <flux:input type="text" wire:model="notes.{{ $item['id'] }}" placeholder="Notes"
                                    class="w-full placeholder-indigo-400 text-indigo-200 text-xs" />
                            </td> --}}

                            <!-- Action -->
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end items-center space-x-2">
                                    <flux:button size="xs" variant="primary" wire:click="saveItem({{ $item['id'] }})">
                                        <span class="text-xs">Save</span>
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                        @endforeach

                        <!-- Totals Row -->
                        <tr class="bg-indigo-900/30 font-bold border-t-2 border-indigo-400/50">
                            <td class="px-6 py-6 text-sm font-bold text-indigo-100">TOTAL</td>
                            {{-- <td class="px-6 py-6"></td> --}}
                            <td class="px-6 py-6 text-center text-indigo-200">—</td>
                            <td class="px-8 py-6 text-center border-l-2 border-r-2 border-blue-400/30 bg-blue-900/20">
                                <div class="text-blue-200 font-bold text-xl"> {{
                                    number_format($this->getTotalEstimated(),2) }}</div>
                            </td>
                            <td
                                class="px-8 py-6 text-center border-l-2 border-r-2 border-purple-400/30 bg-purple-900/20">
                                <div class="text-purple-200 font-bold text-xl"> {{
                                    number_format($this->getTotalActual(),2) }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="space-y-1">
                                    <div
                                        class="font-bold {{ $this->getTotalDifference() < 0 ? 'text-red-400' : ($this->getTotalDifference() > 0 ? 'text-red-400' : 'text-green-400') }}">
                                        <div class="flex items-center justify-center space-x-1">
                                            <span>{{ $this->getTotalDifference() < 0 ? '' : '+' }}{{
                                                    number_format($this->getTotalDifference(),2) }}</span>
                                            @if ($this->getTotalDifference() != 0)
                                            @if ($this->getTotalDifference()
                                            < 0) <flux:icon name="arrow-down" class="h-3 w-3" />
                                            @else
                                            <flux:icon name="arrow-up" class="h-3 w-3" />
                                            @endif
                                            @endif
                                        </div>
                                    </div>
                                    <div
                                        class="text-xs {{ $this->getTotalDifference() < 0 ? 'text-red-300' : ($this->getTotalDifference() > 0 ? 'text-red-300' : 'text-green-300') }}">
                                        {{ $this->getTotalDifferencePercentage() }}%
                                    </div>
                                </div>
                            </td>
                            {{-- <td class="px-6 py-4"></td> --}}
                            <td class="px-6 py-4 text-right">
                                <flux:button wire:click="saveAllChanges" variant="primary"
                                    class="flex items-center text-xs px-3 py-1">
                                    <flux:icon name="check" class="h-4 w-4 mr-1" />
                                    Save All
                                </flux:button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-glass-card>
        @elseif($selectedJobBookingId)
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm">
            <div class="text-center py-12">
                <flux:icon name="exclamation-triangle" class="h-12 w-12 mx-auto text-indigo-400 mb-4" />
                <h3 class="text-lg font-medium text-indigo-100 mb-2">No Costing Items Found</h3>
                <p class="text-indigo-300">The selected job booking doesn't have any costing items yet.</p>
            </div>
        </x-glass-card>
        @endif

        <!-- Success Message -->
        @if (session()->has('message'))
        <div class="fixed top-4 right-4 z-50">
            <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
                {{ session('message') }}
            </div>
        </div>
        @endif

    </div>
</div>