<?php

use Livewire\Volt\Component;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Organization;
use App\Models\JobBooking;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

new class extends Component {
    use WithPagination;

    // Transaction Entry fields
    public $date;
    public $reference;
    public $description;
    public $organization_id = "";
    public $job_booking_id = "";
    public $entries = [];

    // Edit mode
    public $editingTransactionId = null;
    public $isEditing = false;
    public $searchQuery = "";
    public $filterStatus = "";
    public $filterDateFrom = "";
    public $filterDateTo = "";
    public $filterType = "";

    // View mode
    public $viewingTransactionId = null;
    public $transactionDetails = null;

    public function mount()
    {
        $this->resetEntriesForm();
        $this->date = now()->format("Y-m-d");

        // Set default organization if only one exists
        $organization = Organization::first();
        if ($organization) {
            $this->organization_id = $organization->id;
        }

        // Set filter date ranges to current month by default
        $this->filterDateFrom = now()->startOfMonth()->format("Y-m-d");
        $this->filterDateTo = now()->endOfMonth()->format("Y-m-d");
    }

    protected function rules()
    {
        return [
            "date" => "required|date",
            "reference" => "required|string|max:255",
            "description" => "required|string",
            "organization_id" => "required|exists:organizations,id",
            "job_booking_id" => "nullable|exists:job_bookings,id",
            "entries" => "required|array|min:2",
            "entries.*.account_id" => "required|exists:chart_of_accounts,id",
            "entries.*.description" => "nullable|string",
            "entries.*.debit" => "required_without:entries.*.credit|numeric|min:0",
            "entries.*.credit" => "required_without:entries.*.debit|numeric|min:0",
        ];
    }

    public function addEntry()
    {
        $this->entries[] = [
            "account_id" => "",
            "description" => "",
            "debit" => null,
            "credit" => null,
        ];
    }

    public function removeEntry($index)
    {
        unset($this->entries[$index]);
        $this->entries = array_values($this->entries);
    }

    public function saveTransactionEntry()
    {
        $this->validate();

        // Check if debits and credits balance
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($this->entries as $entry) {
            $totalDebit += floatval($entry["debit"] ?? 0);
            $totalCredit += floatval($entry["credit"] ?? 0);
        }

        if (abs($totalDebit - $totalCredit) > 0.001) {
            $this->addError("entries", "Transaction entries must balance (total debits must equal total credits)");
            return;
        }

        // Start transaction
        DB::beginTransaction();

        try {
            $entryData = [];

            foreach ($this->entries as $entry) {
                $accountId = $entry["account_id"];
                $debit = floatval($entry["debit"] ?? 0);
                $credit = floatval($entry["credit"] ?? 0);

                // Calculate amount for transaction entry (but don't update account balance yet)
                $account = Account::find($accountId);
                in_array($account->type, ["asset", "expense"])
                    ? // For Asset and Expense accounts:
                    // - Debit increases the balance
                    // - Credit decreases the balance
                    ($amount = $debit - $credit)
                    : // For Liability, Equity, and Income accounts:
                    // - Credit increases the balance
                    // - Debit decreases the balance
                    ($amount = $credit - $debit);

                $entryData[] = [
                    "account_id" => $accountId,
                    "description" => $entry["description"] ?? $this->description,
                    "debit" => $debit,
                    "credit" => $credit,
                    "amount" => $amount,
                ];
            }

            // Create the main transaction as DRAFT
            $transaction = Transaction::create([
                "date" => $this->date,
                "reference" => $this->reference,
                "description" => $this->description,
                "status" => Transaction::STATUS_DRAFT,
                "organization_id" => $this->organization_id,
                "job_booking_id" => $this->job_booking_id ?: null,
                "transaction_type" => "transaction",
                "created_by" => auth()->id(),
                "amount" => $totalDebit, // Use the total debit amount (which equals the total credit)
            ]);

            // Create the transaction entries
            foreach ($entryData as $entry) {
                $transaction->entries()->create($entry);
            }

            DB::commit();

            $this->dispatch("transaction-entry-created", "Transaction entry created as draft successfully");
            $this->resetForm();
            $this->modal("transaction-from")->close();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError("general", "Error creating transaction entry: " . $e->getMessage());
        }
    }

    public function viewTransactionDetails($transactionId)
    {
        $this->viewingTransactionId = $transactionId;
        $this->transactionDetails = Transaction::with(["entries.account", "organization", "creator", "jobBooking"])->findOrFail($transactionId);

        $this->modal("transaction-details")->show();
    }

    public function generatePdf($transactionId)
    {
        $transaction = Transaction::with(["entries.account", "organization", "creator", "jobBooking"])->findOrFail($transactionId);

        $pdf = PDF::loadView("livewire.accounting.pdf.transaction-entry", [
            "transaction" => $transaction,
        ]);

        $filename = "transaction-entry-" . (string) $transaction->id . ".pdf";

        return response()->streamDownload(fn() => print $pdf->output(), $filename);
    }

    public function reverseTransactionEntry($transactionId)
    {
        DB::beginTransaction();
        try {
            $transaction = Transaction::with("entries.account")->findOrFail($transactionId);
            $reversal = $transaction->createReversal();

            DB::commit();
            $this->dispatch("transaction-entry-reversed", "Transaction entry reversed successfully");

            // Close the modal if it's open
            if ($this->viewingTransactionId === $transactionId) {
                $this->modal("transaction-details")->close();
                $this->viewingTransactionId = null;
                $this->transactionDetails = null;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError("general", "Error reversing transaction entry: " . $e->getMessage());
        }
    }

    public function postTransaction($transactionId)
    {
        try {
            $transaction = Transaction::findOrFail($transactionId);

            // Check if transaction is already posted
            if ($transaction->status === Transaction::STATUS_POSTED) {
                $this->addError("general", "Transaction is already posted");
                return;
            }

            // Use the model method to post the transaction
            $transaction->post();

            $this->dispatch("transaction-posted", "Transaction posted successfully");

            // Refresh the transaction details
            $this->transactionDetails = Transaction::with(["entries.account", "organization", "creator", "jobBooking"])->findOrFail($transactionId);
        } catch (\Exception $e) {
            $this->addError("general", "Error posting transaction: " . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->reset(["reference", "description", "job_booking_id", "editingTransactionId", "isEditing"]);
        $this->date = now()->format("Y-m-d");
        $this->resetEntriesForm();
        $this->resetValidation();
    }

    public function resetEntriesForm()
    {
        $this->entries = [
            [
                "account_id" => "",
                "description" => "",
                "debit" => null,
                "credit" => 0, // First row has debit only, credit set to 0
            ],
            [
                "account_id" => "",
                "description" => "",
                "debit" => 0, // Second row has credit only, debit set to 0
                "credit" => null,
            ],
        ];
    }

    public function cancelEdit()
    {
        $this->resetForm();
        $this->modal("transaction-from")->close();
    }

    public function resetFilters()
    {
        $this->reset(["searchQuery", "filterStatus", "filterType"]);
        $this->filterDateFrom = now()->startOfMonth()->format("Y-m-d");
        $this->filterDateTo = now()->endOfMonth()->format("Y-m-d");
    }

    public function getTotalDebits()
    {
        $total = 0;
        foreach ($this->entries as $entry) {
            $total += floatval($entry["debit"] ?? 0);
        }
        return $total;
    }

    public function getTotalCredits()
    {
        $total = 0;
        foreach ($this->entries as $entry) {
            $total += floatval($entry["credit"] ?? 0);
        }
        return $total;
    }

    public function isBalanced()
    {
        return abs($this->getTotalDebits() - $this->getTotalCredits()) < 0.001;
    }

    public function createFromJobBooking($jobId)
    {
        $job = JobBooking::findOrFail($jobId);

        $this->job_booking_id = $job->id;
        $this->reference = "JOB-" . (string) $job->job_number;
        $this->description = "Transaction entry for job: " . (string) $job->campaign;

        $this->modal("transaction-from")->open();
    }

    public function getTransactions()
    {
        $query = Transaction::with(["entries", "entries.account", "jobBooking"])
            ->where("transaction_type", "transaction")
            ->when($this->searchQuery, function ($query, $search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where("reference", "like", "%{$search}%")->orWhere("description", "like", "%{$search}%");
                });
            })
            ->when($this->filterStatus, function ($query, $status) {
                $query->where("status", $status);
            })
            ->when($this->filterDateFrom, function ($query, $date) {
                $query->whereDate("date", ">=", $date);
            })
            ->when($this->filterDateTo, function ($query, $date) {
                $query->whereDate("date", "<=", $date);
            })
            ->when($this->filterType, function ($query, $type) {
                $query->where("transaction_type", $type);
            })
            ->orderBy("date", "desc");

        return $query;
    }

    public function with(): array
    {
        $results = $this->getTransactions();

        return [
            "transactions" => $results->paginate(10),
            "accounts" => Account::where("is_active", true)->orderBy("account_number")->get(),
            "organizations" => Organization::orderBy("name")->get(),
            "jobBookings" => JobBooking::where("status", "open")->orderBy("created_at", "desc")->get(),
        ];
    }
};
?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <div class="flex items-center space-x-2">
                    <flux:icon name="book-open" class="h-10 w-10" />
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-slate-100">Transaction Entries
                    </h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Manage your financial transactions with double-entry
                    accounting</p>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Add transaction entry button -->
                <flux:modal.trigger name="transaction-from">
                    <flux:button variant="primary" class="flex items-center">
                        <flux:icon name="plus" class="h-4 w-4 mr-2" />
                        <span>New Transaction Entry</span>
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- Filters -->
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm mb-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-4">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">
                    <flux:input type="text" wire:model.live.debounce.300ms="searchQuery"
                        class="block w-full rounded-lg border-0 focus:ring-2 focus:ring-indigo-500"
                        placeholder="Search Transaction entries...">
                        <div slot="leadingIcon"
                            class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                            <flux:icon name="magnifying-glass" class="h-4 w-4 text-indigo-300" />
                        </div>
                    </flux:input>
                </div>

                <div class="flex flex-col md:flex-row items-center space-y-3 md:space-y-0 md:space-x-4">
                    <div class="flex items-center space-x-2 w-full md:w-auto">
                        <flux:input type="date" wire:model.live="filterDateFrom"
                            class="rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500" />
                        <span class="text-indigo-300">to</span>
                        <flux:input type="date" wire:model.live="filterDateTo"
                            class="rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500" />
                    </div>

                    <div class="w-full md:w-auto">
                        <flux:select wire:model.live="filterStatus"
                            class="rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500">
                            <flux:select.option value="">All Statuses</flux:select.option>
                            <flux:select.option value="draft">Draft</flux:select.option>
                            <flux:select.option value="posted">Posted</flux:select.option>
                            <flux:select.option value="void">Void</flux:select.option>
                        </flux:select>
                    </div>

                    <div class="w-full md:w-auto">
                        <flux:select wire:model.live="filterType"
                            class="rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500">
                            <flux:select.option value="">All Types</flux:select.option>
                            <flux:select.option value="journal">Journal</flux:select.option>
                            <flux:select.option value="bank">Bank</flux:select.option>
                            <flux:select.option value="cash">Cash</flux:select.option>
                        </flux:select>
                    </div>

                    <flux:button wire:click="resetFilters" variant="danger" size="sm" class="w-full md:w-auto">
                        Reset
                    </flux:button>
                </div>
            </div>
        </x-glass-card>

        <!-- Transaction entries table -->
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm mb-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-indigo-200 mb-3 md:mb-0">Transaction Entries</h2>
                <span class="text-sm text-indigo-300">{{ $transactions->total() }}
                    {{ Str::plural('entry', $transactions->total()) }}</span>
            </div>

            <div class="overflow-x-auto rounded-lg">
                <table class="min-w-full divide-y divide-indigo-200/20">
                    <thead class="bg-gradient-to-r from-indigo-900/40 to-indigo-800/30 backdrop-blur-sm">
                        <tr>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-indigo-300 uppercase tracking-wider">
                                Date
                            </th>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-indigo-300 uppercase tracking-wider">
                                Reference
                            </th>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-indigo-300 uppercase tracking-wider">
                                Description
                            </th>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-indigo-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col"
                                class="px-4 py-3 text-center text-xs font-medium text-indigo-300 uppercase tracking-wider">
                                Job
                            </th>
                            <th scope="col"
                                class="px-4 py-3 text-right text-xs font-medium text-indigo-300 uppercase tracking-wider">
                                Amount
                            </th>
                            <th scope="col"
                                class="px-4 py-3 text-center text-xs font-medium text-indigo-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-indigo-900/10 backdrop-blur-sm divide-y divide-indigo-200/10">
                        @forelse ($transactions as $transaction)
                        <tr class="hover:bg-indigo-900/20 transition-colors duration-200">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-indigo-100">
                                {{ $transaction->date->format('Y-m-d') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-sm font-medium text-indigo-100">{{ $transaction->reference }}</span>
                            </td>
                            <td class="px-4 py-3 max-w-xs">
                                <div class="text-sm text-indigo-200 truncate">{{ $transaction->description }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full
                                        @if ($transaction->status === 'posted') bg-emerald-100/20 text-emerald-400
                                        @elseif($transaction->status === 'draft') bg-amber-100/20 text-amber-400
                                        @else bg-rose-100/20 text-rose-400 @endif">
                                    {{ ucfirst($transaction->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                                @if ($transaction->jobBooking)
                                <span class="text-indigo-200">{{ $transaction->jobBooking->job_number }}</span>
                                @else
                                <span class="text-indigo-400/50">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium text-indigo-100">
                                @php
                                $total = $transaction->entries->sum('debit');
                                @endphp
                                {{ $transaction->formatMoney($total) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                                <div class="flex justify-center space-x-2">
                                    <flux:button wire:click="viewTransactionDetails({{ $transaction->id }})" size="xs"
                                        variant="primary" class="inline-flex items-center">
                                        View
                                    </flux:button>
                                    {{-- @if ($transaction->status !== 'void')
                                    <flux:button wire:click="reverseTransactionEntry({{ $transaction->id }})" size="xs"
                                        variant="danger" class="inline-flex items-center"
                                        confirm-text="Are you sure you want to reverse this transaction entry? This will create a new entry that cancels out this transaction."
                                        confirm-button-text="Yes, Reverse" cancel-button-text="No, Cancel">
                                        Reverse
                                    </flux:button>
                                    @endif --}}
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-indigo-300">
                                No Transaction entries found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $transactions->links() }}
            </div>
        </x-glass-card>

        <!-- Transaction Entry Form Modal -->
        <flux:modal name="transaction-from" class="w-full max-w-4xl">
            <x-glass-card colorScheme="indigo" class="overflow-hidden">
                <form wire:submit="saveTransactionEntry">
                    <!-- Form header -->
                    <div class="flex justify-between items-center border-b border-indigo-200/20 p-4">
                        <h3 class="text-lg font-semibold text-indigo-100">Create Transaction Entry</h3>
                    </div>

                    <!-- Form content -->
                    <div class="p-5 space-y-6">
                        <!-- Form error messages -->
                        @error('general')
                        <div class="bg-rose-500/20 text-rose-300 px-4 py-2 rounded mb-4">
                            {{ $message }}
                        </div>
                        @enderror

                        @error('entries')
                        <div class="bg-rose-500/20 text-rose-300 px-4 py-2 rounded mb-4">
                            {{ $message }}
                        </div>
                        @enderror

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <!-- Date -->
                            <div class="col-span-1">
                                <label for="date" class="block text-sm font-medium text-indigo-300 mb-1">
                                    Date <span class="text-rose-400">*</span>
                                </label>
                                <flux:input type="date" wire:model="date" id="date"
                                    class="rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500 w-full" />
                                @error('date')
                                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Reference -->
                            <div class="col-span-1">
                                <label for="reference" class="block text-sm font-medium text-indigo-300 mb-1">
                                    Reference Type <span class="text-rose-400">*</span>
                                </label>
                                <flux:select wire:model="reference" id="reference"
                                    class="rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500 w-full">
                                    <flux:select.option value="">Select Reference Type</flux:select.option>
                                    <flux:select.option value="Cash">Cash Voucher</flux:select.option>
                                    <flux:select.option value="Bank">Bank Voucher</flux:select.option>
                                    <flux:select.option value="JV">Journal Voucher</flux:select.option>
                                </flux:select>
                                @error('reference')
                                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Organization -->
                            <div class="col-span-1">
                                <label for="organization_id" class="block text-sm font-medium text-indigo-300 mb-1">
                                    Organization <span class="text-rose-400">*</span>
                                </label>
                                <flux:select wire:model="organization_id" id="organization_id"
                                    class="rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500 w-full">
                                    <flux:select.option value="">Select Organization</flux:select.option>
                                    @foreach ($organizations as $organization)
                                    <flux:select.option value="{{ $organization->id }}">{{ $organization->name }}
                                    </flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('organization_id')
                                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="col-span-2">
                                <label for="description" class="block text-sm font-medium text-indigo-300 mb-1">
                                    Description <span class="text-rose-400">*</span>
                                </label>
                                <flux:textarea wire:model="description" id="description" rows="1"
                                    class="rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500 w-full" />
                                @error('description')
                                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Job Booking -->
                            <div class="col-span-1">
                                <label for="job_booking_id" class="block text-sm font-medium text-indigo-300 mb-1">
                                    Job Booking
                                </label>
                                <flux:select wire:model="job_booking_id" id="job_booking_id"
                                    class="rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500 w-full">
                                    <flux:select.option value="">Select Job (Optional)</flux:select.option>
                                    @foreach ($jobBookings as $job)
                                    <flux:select.option value="{{ $job->id }}">{{ $job->job_number }} -
                                        {{ $job->campaign }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('job_booking_id')
                                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Transaction Entries -->
                        <div class="mt-6">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-lg font-semibold text-indigo-200">Transaction Entries</h3>
                                <div class="flex items-center space-x-3">
                                    <div
                                        class="text-sm @if (!$this->isBalanced() && count($this->entries) > 0) text-rose-400 @else text-emerald-400 @endif">
                                        @if ($this->isBalanced() && count($this->entries) > 0)
                                        <span class="inline-flex items-center">
                                            <flux:icon name="check-circle" class="h-4 w-4 mr-1" />
                                            Balanced
                                        </span>
                                        @elseif(!$this->isBalanced() && count($this->entries) > 0)
                                        <span class="inline-flex items-center">
                                            <flux:icon name="x-circle" class="h-4 w-4 mr-1" />
                                            Unbalanced
                                        </span>
                                        @endif
                                    </div>
                                    <flux:button wire:click="addEntry" type="button" size="xs" variant="primary"
                                        class="inline-flex items-center">
                                        Add Line
                                    </flux:button>
                                </div>
                            </div>

                            <div class="overflow-x-auto border border-indigo-200/20 rounded-lg">
                                <table class="min-w-full divide-y divide-indigo-200/20">
                                    <thead class="bg-indigo-900/30">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-indigo-300 uppercase tracking-wider w-1/3">
                                                Account
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-indigo-300 uppercase tracking-wider">
                                                Description
                                            </th>
                                            <th
                                                class="px-4 py-3 text-right text-xs font-medium text-indigo-300 uppercase tracking-wider w-1/6">
                                                Debit
                                            </th>
                                            <th
                                                class="px-4 py-3 text-right text-xs font-medium text-indigo-300 uppercase tracking-wider w-1/6">
                                                Credit
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center text-xs font-medium text-indigo-300 uppercase tracking-wider w-16">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-indigo-900/10 backdrop-blur-sm divide-y divide-indigo-200/10">
                                        @foreach ($entries as $index => $entry)
                                        <tr class="hover:bg-indigo-900/20 transition-colors duration-200">
                                            <td class="px-4 py-2">
                                                <flux:select wire:model="entries.{{ $index }}.account_id"
                                                    class="text-sm rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500 w-full">
                                                    <flux:select.option value="">Select Account
                                                    </flux:select.option>
                                                    @foreach ($accounts as $account)
                                                    <flux:select.option value="{{ $account->id }}">
                                                        {{ $account->account_number }} - {{ $account->name }}
                                                    </flux:select.option>
                                                    @endforeach
                                                </flux:select>
                                                @error('entries.' . $index . '.account_id')
                                                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                                                @enderror
                                            </td>
                                            <td class="px-4 py-2">
                                                <flux:input wire:model="entries.{{ $index }}.description" type="text"
                                                    placeholder="Description (optional)"
                                                    class="text-sm rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500 w-full" />
                                            </td>
                                            <td class="px-4 py-2">
                                                @if ($index === 0)
                                                <flux:input wire:model.live="entries.{{ $index }}.debit" type="number"
                                                    step="0.01" min="0" placeholder="0.00"
                                                    class="text-sm text-right rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500 w-full" />
                                                @elseif ($index === 1)
                                                <div class="text-center text-xs text-indigo-400/50">— (Debit
                                                    not allowed here)</div>
                                                <input type="hidden" wire:model.live="entries.{{ $index }}.debit"
                                                    value="0" />
                                                @else
                                                <flux:input wire:model.live="entries.{{ $index }}.debit" type="number"
                                                    step="0.01" min="0" placeholder="0.00"
                                                    class="text-sm text-right rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500 w-full" />
                                                @endif
                                            </td>
                                            <td class="px-4 py-2">
                                                @if ($index === 0)
                                                <div class="text-center text-xs text-indigo-400/50">— (Credit
                                                    not allowed here)</div>
                                                <input type="hidden" wire:model.live="entries.{{ $index }}.credit"
                                                    value="0" />
                                                @elseif ($index === 1)
                                                <flux:input wire:model.live="entries.{{ $index }}.credit" type="number"
                                                    step="0.01" min="0" placeholder="0.00"
                                                    class="text-sm text-right rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500 w-full" />
                                                @else
                                                <flux:input wire:model.live="entries.{{ $index }}.credit" type="number"
                                                    step="0.01" min="0" placeholder="0.00"
                                                    class="text-sm text-right rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500 w-full" />
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                @if (count($entries) > 2)
                                                <button wire:click="removeEntry({{ $index }})" type="button"
                                                    class="text-rose-400 hover:text-rose-300 transition-colors">
                                                    <flux:icon name="trash" class="h-4 w-4" />
                                                </button>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach

                                        <!-- Totals row -->
                                        <tr class="bg-indigo-900/30">
                                            <td colspan="2"
                                                class="px-4 py-3 text-right text-sm font-medium text-indigo-300">
                                                Totals
                                            </td>
                                            <td
                                                class="px-4 py-3 text-right text-sm font-medium @if (!$this->isBalanced() && count($this->entries) > 0) text-rose-400 @else text-indigo-300 @endif">
                                                {{ number_format($this->getTotalDebits(), 2) }}
                                            </td>
                                            <td
                                                class="px-4 py-3 text-right text-sm font-medium @if (!$this->isBalanced() && count($this->entries) > 0) text-rose-400 @else text-indigo-300 @endif">
                                                {{ number_format($this->getTotalCredits(), 2) }}
                                            </td>
                                            <td class="px-4 py-3"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-4 border-t border-indigo-200/20 flex justify-end space-x-3">
                        <flux:button type="button" wire:click="cancelEdit" variant="primary">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary" class="inline-flex items-center">
                            Save Transaction Entry
                        </flux:button>
                    </div>
                </form>
            </x-glass-card>
        </flux:modal>

        <!-- Transaction Details Modal -->
        <flux:modal name="transaction-details" class="w-full max-w-5xl">
            @if ($transactionDetails)
            <x-glass-card colorScheme="indigo" class="overflow-hidden">
                <!-- Modal header -->
                <div class="flex justify-between items-center border-b border-indigo-200/20 p-4">
                    <h3 class="text-lg font-semibold text-indigo-100">Transaction Entry Details</h3>
                </div>

                <div class="p-5">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-indigo-100 mb-1">
                                {{ $transactionDetails->reference }}</h2>
                            <p class="text-sm text-indigo-300">Date:
                                {{ $transactionDetails->date->format('Y-m-d') }}</p>
                            <p class="text-sm text-indigo-300">Organization:
                                {{ $transactionDetails->organization ? $transactionDetails->organization->name : 'N/A'
                                }}
                            </p>
                            @if ($transactionDetails->jobBooking)
                            <p class="text-sm text-indigo-300">Job:
                                {{ $transactionDetails->jobBooking->job_number }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full
                                    @if ($transactionDetails->status === 'posted') bg-emerald-100/20 text-emerald-400
                                    @elseif($transactionDetails->status === 'draft') bg-amber-100/20 text-amber-400
                                    @else bg-rose-100/20 text-rose-400 @endif">
                                {{ ucfirst($transactionDetails->status) }}
                            </span>
                            <p class="text-sm text-indigo-300 mt-2">
                                Created by: {{ $transactionDetails->creator->name ?? 'Unknown' }}
                            </p>
                            <p class="text-sm text-indigo-300">
                                Created on: {{ $transactionDetails->created_at->format('Y-m-d H:i') }}
                            </p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-indigo-300 mb-1">Description</h3>
                        <p class="text-indigo-100">{{ $transactionDetails->description }}</p>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-indigo-300 mb-3">Transaction Entries</h3>
                        @if ($transactionDetails->status === 'draft')
                        <div class="mb-4 p-3 bg-amber-900/20 border border-amber-500/30 rounded-lg">
                            <div class="flex items-center">
                                <flux:icon name="exclamation-triangle" class="h-5 w-5 text-amber-400 mr-2" />
                                <p class="text-sm text-amber-200">
                                    This transaction is in draft status and has not affected account balances yet.
                                    Click "Post Entry" to apply the changes to account balances.
                                </p>
                            </div>
                        </div>
                        @endif
                        <div class="overflow-x-auto border border-indigo-200/20 rounded-lg">
                            <table class="min-w-full divide-y divide-indigo-200/20">
                                <thead class="bg-indigo-900/30">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-indigo-300 uppercase tracking-wider">
                                            Account
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-indigo-300 uppercase tracking-wider">
                                            Description
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right text-xs font-medium text-indigo-300 uppercase tracking-wider">
                                            Debit
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right text-xs font-medium text-indigo-300 uppercase tracking-wider">
                                            Credit
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-indigo-900/10 backdrop-blur-sm divide-y divide-indigo-200/10">
                                    @foreach ($transactionDetails->entries as $entry)
                                    <tr class="hover:bg-indigo-900/20 transition-colors duration-200">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-indigo-100">
                                            @if ($entry->account)
                                            {{ $entry->account->account_number }} -
                                            {{ $entry->account->name }}
                                            @else
                                            Account Not Found
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-indigo-300">
                                            {{ $entry->description }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-indigo-100">
                                            {{ $entry->debit > 0 ? $entry->formatMoney($entry->debit) : '' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-indigo-100">
                                            {{ $entry->credit > 0 ? $entry->formatMoney($entry->credit) : '' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                    <!-- Totals row -->
                                    <tr class="bg-indigo-900/30 font-medium">
                                        <td colspan="2" class="px-4 py-3 text-right text-sm text-indigo-200">
                                            Totals
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-indigo-100">
                                            {{
                                            $transactionDetails->formatMoney($transactionDetails->entries->sum('debit'))
                                            }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-indigo-100">
                                            {{
                                            $transactionDetails->formatMoney($transactionDetails->entries->sum('credit'))
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 mt-6">
                        @if ($transactionDetails->status === 'draft')
                        <flux:button wire:click="postTransaction({{ $transactionDetails->id }})" variant="ghost"
                            size="sm" class="inline-flex items-center"
                            confirm-text="Are you sure you want to post this transaction entry? This will affect account balances and cannot be undone."
                            confirm-button-text="Yes, Post" cancel-button-text="No, Cancel">
                            Post Entry
                        </flux:button>
                        @endif
                        <flux:button wire:click="generatePdf({{ $transactionDetails->id }})" variant="primary" size="sm"
                            class="inline-flex items-center">
                            Export PDF
                        </flux:button>
                        {{-- @if ($transactionDetails->status !== 'void')
                        <flux:button wire:click="reverseTransactionEntry({{ $transactionDetails->id }})" variant="danger"
                            size="sm" class="inline-flex items-center"
                            confirm-text="Are you sure you want to reverse this transaction entry? This will create a new entry that cancels out this transaction."
                            confirm-button-text="Yes, Reverse" cancel-button-text="No, Cancel">
                            Reverse Entry
                        </flux:button>
                        @endif --}}
                    </div>
                </div>
            </x-glass-card>
            @endif
        </flux:modal>
    </div>
</div>
