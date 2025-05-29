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

    // Job Booking fields
    public $job_number = '';
    public $campaign = '';
    public $client_id = '';
    public $organization_id = '';
    public $sale_by = '';
    public $po_number = '';
    public $approved_budget = null;
    public $gst = false;
    public $status = 'open';

    // Job Costing items
    public $items = [];

    // Edit mode
    public $editingJobId = null;
    public $isEditing = false;
    public $searchQuery = '';

    public function mount()
    {
        $this->resetItemsForm();
    }

    protected function rules()
    {
        return [
            'campaign' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'organization_id' => 'required|exists:organizations,id',
            'sale_by' => 'required|string|max:255',
            'po_number' => 'required|string|max:255',
            'approved_budget' => 'nullable|numeric|min:0',
            'gst' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.vendor_id' => 'required|exists:vendors,id',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.total_amount' => 'required|numeric|min:0',
        ];
    }

    public function addItem()
    {
        $this->items[] = [
            'vendor_id' => '',
            'item_id' => '',
            'quantity' => 1,
            'rate' => 0,
            'total_amount' => 0,
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function calculateTotal($index)
    {
        if (isset($this->items[$index]['quantity']) && isset($this->items[$index]['rate'])) {
            $quantity = (int) $this->items[$index]['quantity'];
            $rate = (float) $this->items[$index]['rate'];
            $this->items[$index]['total_amount'] = $quantity * $rate;
        }
    }

    public function saveJobBooking()
    {
        $this->validate();

        if ($this->isEditing) {
            $jobBooking = JobBooking::find($this->editingJobId);
            $jobBooking->update([
                'campaign' => $this->campaign,
                'client_id' => $this->client_id,
                'organization_id' => $this->organization_id,
                'sale_by' => $this->sale_by,
                'po_number' => $this->po_number,
                'approved_budget' => $this->approved_budget,
                'gst' => $this->gst,
            ]);

            // Delete existing job costings and recreate
            $jobBooking->jobCostings()->delete();

            foreach ($this->items as $item) {
                JobCosting::create([
                    'job_id' => $jobBooking->id,
                    'vendor_id' => $item['vendor_id'],
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'total_amount' => $item['total_amount'],
                ]);
            }

            $this->dispatch('job-updated', 'Job booking updated successfully');
        } else {
            // Create a new job booking
            $jobModel = new JobBooking();
            $job_number = $jobModel->generateJobNumber($this->organization_id);

            $jobBooking = JobBooking::create([
                'job_number' => $job_number,
                'campaign' => $this->campaign,
                'client_id' => $this->client_id,
                'organization_id' => $this->organization_id,
                'sale_by' => $this->sale_by,
                'po_number' => $this->po_number,
                'approved_budget' => $this->approved_budget,
                'gst' => $this->gst,
                'status' => 'open',
            ]);

            foreach ($this->items as $item) {
                JobCosting::create([
                    'job_id' => $jobBooking->id,
                    'vendor_id' => $item['vendor_id'],
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'total_amount' => $item['total_amount'],
                ]);
            }

            $this->dispatch('job-created', 'Job booking created successfully');
        }

        $this->resetForm();
        $this->modal('job-form')->close();
    }

    public function editJobBooking($jobId)
    {
        $this->isEditing = true;
        $this->editingJobId = $jobId;

        $jobBooking = JobBooking::with('jobCostings')->find($jobId);
        $this->job_number = $jobBooking->job_number;
        $this->campaign = $jobBooking->campaign;
        $this->client_id = $jobBooking->client_id;
        $this->organization_id = $jobBooking->organization_id;
        $this->sale_by = $jobBooking->sale_by;
        $this->po_number = $jobBooking->po_number;
        $this->approved_budget = $jobBooking->approved_budget;
        $this->gst = $jobBooking->gst;
        $this->status = $jobBooking->status;

        // Load job costing items
        $this->items = [];
        foreach ($jobBooking->jobCostings as $costing) {
            $this->items[] = [
                'vendor_id' => $costing->vendor_id,
                'item_id' => $costing->item_id,
                'quantity' => $costing->quantity,
                'rate' => $costing->rate,
                'total_amount' => $costing->total_amount,
            ];
        }

        $this->modal('job-form')->show();
    }

    public function closeJob($jobId)
    {
        $jobBooking = JobBooking::find($jobId);
        $jobBooking->update(['status' => 'closed']);
        $this->dispatch('job-closed', 'Job booking closed successfully');
    }

    public function reopenJob($jobId)
    {
        $jobBooking = JobBooking::find($jobId);
        $jobBooking->update(['status' => 'open']);
        $this->dispatch('job-reopened', 'Job booking reopened successfully');
    }

    public function deleteJobBooking($jobId)
    {
        JobBooking::destroy($jobId);
        $this->dispatch('job-deleted', 'Job booking deleted successfully');
    }

    public function resetForm()
    {
        $this->reset(['job_number', 'campaign', 'client_id', 'organization_id', 'sale_by', 'po_number', 'approved_budget', 'gst', 'status', 'editingJobId', 'isEditing']);
        $this->resetItemsForm();
        $this->resetValidation();
    }

    public function resetItemsForm()
    {
        $this->items = [
            [
                'vendor_id' => '',
                'item_id' => '',
                'quantity' => 1,
                'rate' => 0,
                'total_amount' => 0,
            ],
        ];
    }

    public function cancelEdit()
    {
        $this->resetForm();
        $this->modal('job-form')->close();
    }

    public function with(): array
    {
        $query = JobBooking::with(['client', 'organization', 'jobCostings'])
            ->when($this->searchQuery, function ($query, $search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery
                        ->where('job_number', 'like', "%{$search}%")
                        ->orWhere('campaign', 'like', "%{$search}%")
                        ->orWhere('sale_by', 'like', "%{$search}%")
                        ->orWhere('po_number', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('organization', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc');

        return [
            'jobBookings' => $query->paginate(10),
            'clients' => Client::orderBy('name')->get(),
            'vendors' => Vendor::orderBy('name')->get(),
            'itemsList' => Item::orderBy('name')->get(),
            'organizations' => Organization::orderBy('name')->get(),
        ];
    }

    public function calculateTotalBudget()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += (float) ($item['total_amount'] ?? 0);
        }
        return $total;
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <div class="flex items-center space-x-2">
                    <flux:icon name="clipboard-document-list" class="h-10 w-10" />
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-slate-100">Job Bookings</h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Manage your job bookings and client projects</p>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Add job button -->
                <flux:modal.trigger name="job-form">
                    <flux:button variant="primary" class="flex items-center">
                        <flux:icon name="plus" class="h-4 w-4 mr-2" />
                        <span>New Job Booking</span>
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- Job Booking Form -->
        <flux:modal name="job-form" class="w-full max-w-6xl">
            <x-glass-card colorScheme="indigo" class="overflow-hidden">
                <form wire:submit="saveJobBooking">
                    <div class="space-y-6">
                        <!-- Basic Info Section -->
                        <div class="border-b border-indigo-200/20 pb-6">
                            <h3 class="text-lg font-medium text-indigo-100">Basic Information</h3>

                            @if ($isEditing)
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium">Job Number</label>
                                    <div
                                        class="mt-1 p-2 bg-indigo-900/20 rounded-md border border-indigo-300/10 text-indigo-100">
                                        {{ $job_number }}
                                    </div>
                                </div>
                            </div>
                            @endif
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label for="organization_id" class="block text-sm font-medium">Organization</label>
                                    <flux:select id="organization_id" wire:model="organization_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <flux:select.option value="">Select organization</flux:select.option>
                                        @foreach ($organizations as $organization)
                                        <flux:select.option value="{{ $organization->id }}">
                                            {{ $organization->name }}
                                        </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @error('organization_id')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label for="client_id" class="block text-sm font-medium">Client</label>
                                    <flux:select id="client_id" wire:model="client_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <flux:select.option value="">Select client</flux:select.option>
                                        @foreach ($clients as $client)
                                        <flux:select.option value="{{ $client->id }}">{{ $client->name }}
                                        </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @error('client_id')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label for="campaign" class="block text-sm font-medium">Campaign</label>
                                    <flux:input id="campaign" type="text" wire:model="campaign"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Enter campaign name">
                                    </flux:input>
                                    @error('campaign')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label for="sale_by" class="block text-sm font-medium">Sale By</label>
                                    <flux:input id="sale_by" type="text" wire:model="sale_by"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Enter sales representative">
                                    </flux:input>
                                    @error('sale_by')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label for="po_number" class="block text-sm font-medium">PO Number</label>
                                    <flux:input id="po_number" type="text" wire:model="po_number"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Enter purchase order number">
                                    </flux:input>
                                    @error('po_number')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Job Costing Items Section -->
                        <div class="border-b border-indigo-200/20 pb-6">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-medium text-indigo-100">Job Costing Items</h3>
                                <flux:button size="xs" type="button" wire:click="addItem" class="flex items-center">
                                    <span>+ Add Item</span>
                                </flux:button>
                            </div>

                            <div class="mt-4 space-y-4">
                                @foreach ($items as $index => $item)
                                <div class="bg-indigo-900/20 p-4 rounded-lg border border-indigo-300/10 relative">
                                    @if (count($items) > 1)
                                    <button type="button" wire:click="removeItem({{ $index }})"
                                        class="absolute top-2 right-2 text-red-400 hover:text-red-300">
                                        <flux:icon name="x-mark" class="h-5 w-5" />
                                    </button>
                                    @endif

                                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                        <div class="md:col-span-1">
                                            <label class="block text-sm font-medium">Vendor</label>
                                            <flux:select wire:model="items.{{ $index }}.vendor_id"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <flux:select.option value="">Select vendor
                                                </flux:select.option>
                                                @foreach ($vendors as $vendor)
                                                <flux:select.option value="{{ $vendor->id }}">
                                                    {{ $vendor->name }}
                                                </flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            @error('items.' . $index . '.vendor_id')
                                            <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="md:col-span-1">
                                            <label class="block text-sm font-medium">Item</label>
                                            <flux:select wire:model="items.{{ $index }}.item_id"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <flux:select.option value="">Select item</flux:select.option>
                                                @foreach ($itemsList as $itemOption)
                                                <flux:select.option value="{{ $itemOption->id }}">
                                                    {{ $itemOption->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            @error('items.' . $index . '.item_id')
                                            <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium">Quantity</label>
                                            <flux:input type="number" min="1" wire:model="items.{{ $index }}.quantity"
                                                wire:change="calculateTotal({{ $index }})"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </flux:input>
                                            @error('items.' . $index . '.quantity')
                                            <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium">Rate</label>
                                            <flux:input type="number" step="0.01" min="0"
                                                wire:model="items.{{ $index }}.rate"
                                                wire:change="calculateTotal({{ $index }})"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </flux:input>
                                            @error('items.' . $index . '.rate')
                                            <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium">Total</label>
                                            <flux:input type="number" step="0.01" readonly
                                                wire:model="items.{{ $index }}.total_amount"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                            </flux:input>
                                            @error('items.' . $index . '.total_amount')
                                            <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                @endforeach

                                @error('items')
                                <span class="text-red-500 text-xs block mt-2">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Budget Section -->
                        <div>
                            <h3 class="text-lg font-medium text-indigo-100">Budget Information</h3>

                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="approved_budget" class="block text-sm font-medium">Approved
                                        Budget</label>
                                    <flux:input id="approved_budget" type="number" step="0.01" min="0"
                                        wire:model="approved_budget"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Enter approved budget">
                                    </flux:input>
                                    @error('approved_budget')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="flex items-center mt-8">
                                    <flux:checkbox id="gst" wire:model="gst" class="mr-2"></flux:checkbox>
                                    <label for="gst" class="text-sm font-medium">Include GST</label>
                                </div>
                            </div>

                            <div class="mt-4 p-4 bg-indigo-900/20 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <div class="text-indigo-100 font-medium">Total Job Cost:</div>
                                    <div class="text-xl font-bold text-indigo-100">
                                        PKR {{ number_format($this->calculateTotalBudget(), 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            @if ($isEditing)
                            <flux:button type="button" variant="danger" wire:click="cancelEdit"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 mr-2">
                                Cancel
                            </flux:button>
                            @endif
                            <flux:button type="submit" variant="primary"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ $isEditing ? 'Update Job Booking' : 'Create Job Booking' }}
                            </flux:button>
                        </div>
                    </div>
                </form>
            </x-glass-card>
        </flux:modal>

        <!-- Job Bookings List -->
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">
                    <flux:input type="text" wire:model.live.debounce.300ms="searchQuery"
                        class="block w-full rounded-lg border-0 focus:ring-2 focus:ring-indigo-500"
                        placeholder="Search job bookings...">
                    </flux:input>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-indigo-300 mr-2">{{ $jobBookings->total() }}
                        {{ Str::plural('job booking', $jobBookings->total()) }}</span>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg">
                <table class="min-w-full divide-y divide-indigo-200/20">
                    <thead class="bg-gradient-to-r from-indigo-900/40 to-indigo-800/30 backdrop-blur-sm">
                        <tr>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Job Number</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Organization</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Client</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Campaign</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Budget</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Status</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <span>Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-indigo-900/10 backdrop-blur-sm divide-y divide-indigo-200/10">
                        @forelse ($jobBookings as $job)
                        <tr class="hover:bg-indigo-900/20 transition-colors duration-200">
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium text-indigo-100">{{ $job->job_number }}</div>
                                <div class="text-xs text-indigo-300 mt-1">
                                    <flux:icon name="calendar" class="inline-block h-3 w-3 mr-1" />
                                    {{ $job->created_at->format('M d, Y') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-200">
                                {{ $job->organization->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-200">
                                {{ $job->client->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-200">
                                {{ $job->campaign }}
                                <div class="text-xs text-indigo-300 mt-1">
                                    <span>PO: {{ $job->po_number }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-200">
                                @if ($job->approved_budget)
                                <div class="font-medium">PKR {{ number_format($job->approved_budget, 2) }}
                                </div>
                                @else
                                <span class="text-indigo-400 italic">Not set</span>
                                @endif
                                <div class="text-xs text-indigo-300 mt-1">
                                    @if ($job->gst)
                                    <span
                                        class="bg-emerald-900/30 text-emerald-300 px-2 py-0.5 rounded-full text-xs">GST
                                        Included</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if ($job->status === 'open')
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-emerald-500/20 text-emerald-200">
                                    <flux:icon name="lock-open" class="inline-block h-3 w-3 mr-1" />
                                    Open
                                </span>
                                @else
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-slate-500/20 text-slate-200">
                                    <flux:icon name="lock-closed" class="inline-block h-3 w-3 mr-1" />
                                    Closed
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end items-center space-x-2">
                                    <flux:button size="xs" variant="primary"
                                        wire:click="editJobBooking({{ $job->id }})">
                                        Edit
                                    </flux:button>

                                    @if ($job->status === 'open')
                                    <flux:button size="xs" variant="danger" wire:click="closeJob({{ $job->id }})">
                                        Close
                                    </flux:button>
                                    @else
                                    <flux:button size="xs" variant="primary" wire:click="reopenJob({{ $job->id }})">
                                        Reopen
                                    </flux:button>
                                    @endif

                                    <flux:button size="xs" variant="danger"
                                        wire:confirm="Are you sure you want to delete this job booking?"
                                        wire:click="deleteJobBooking({{ $job->id }})">
                                        <flux:icon icon="trash" class="h-4 w-4" />
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div
                                    class="flex flex-col items-center justify-center p-6 bg-indigo-900/10 rounded-xl backdrop-blur-sm">
                                    <div
                                        class="w-20 h-20 rounded-full bg-indigo-900/20 flex items-center justify-center mb-4">
                                        <flux:icon name="clipboard-document-list"
                                            class="w-12 h-12 text-indigo-300 dark:text-indigo-400" />
                                    </div>
                                    <h3 class="text-xl font-medium text-slate-800 dark:text-slate-200">No job
                                        bookings
                                        found</h3>
                                    <p class="mt-2 text-slate-500 dark:text-slate-400 max-w-sm">Get started by
                                        creating
                                        your first job booking using the "New Job Booking" button above.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $jobBookings->links() }}
            </div>
        </x-glass-card>

    </div>
</div>