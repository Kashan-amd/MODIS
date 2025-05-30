<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\PettyCash;
use App\Models\Account;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component {
    use WithPagination;

    // Organization selection
    public $selectedOrganizationId = null;
    public $organizations = [];

    // Form properties
    public $account_id = '';
    public $amount = '';
    public $transaction_type = 'expense'; // expense or income
    public $reference = '';
    public $description = '';
    public $transaction_date;

    // Filter properties
    public $search = '';
    public $status_filter = '';
    public $date_from = '';
    public $date_to = '';
    public $account_filter = '';

    // Modal state
    public $showForm = false;
    public $editingId = null;

    public function mount()
    {
        // Get all organizations
        $this->organizations = Organization::all();

        // Set default selected organization if available
        if ($this->organizations->count() > 0) {
            $this->selectedOrganizationId = $this->organizations->first()->id;
        }

        $this->transaction_date = now()->format('Y-m-d');
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to = now()->format('Y-m-d');
    }

    public function updatedSelectedOrganizationId()
    {
        // Reset pagination when organization changes
        $this->resetPage();
    }

    public function rules()
    {
        return [
            'account_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_type' => 'required|in:expense,income',
            'reference' => 'nullable|string|max:255',
            'description' => 'required|string|max:500',
            'transaction_date' => 'required|date',
        ];
    }

    public function save()
    {
        $this->validate();

        if (!$this->selectedOrganizationId) {
            $this->addError('organization', 'Please select an organization.');
            return;
        }

        $data = [
            'organization_id' => $this->selectedOrganizationId,
            'account_id' => $this->account_id,
            'reference' => $this->reference,
            'description' => $this->description,
            'transaction_date' => $this->transaction_date,
            'status' => PettyCash::STATUS_DRAFT,
        ];

        // Set debit/credit based on transaction type
        if ($this->transaction_type === 'expense') {
            $data['debit'] = $this->amount;
            $data['credit'] = 0;
        } else {
            $data['debit'] = 0;
            $data['credit'] = $this->amount;
        }

        if ($this->editingId) {
            $transaction = PettyCash::findOrFail($this->editingId);
            $transaction->update($data);
            session()->flash('message', 'Petty cash transaction updated successfully.');
        } else {
            PettyCash::create($data);
            session()->flash('message', 'Petty cash transaction created successfully.');
        }

        $this->resetForm();
    }

    public function edit($id)
    {
        $transaction = PettyCash::findOrFail($id);

        $this->editingId = $id;
        $this->account_id = $transaction->account_id;
        $this->amount = $transaction->debit > 0 ? $transaction->debit : $transaction->credit;
        $this->transaction_type = $transaction->debit > 0 ? 'expense' : 'income';
        $this->reference = $transaction->reference;
        $this->description = $transaction->description;
        $this->transaction_date = $transaction->transaction_date->format('Y-m-d');

        $this->showForm = true;
    }

    public function delete($id)
    {
        $transaction = PettyCash::findOrFail($id);

        if ($transaction->status === PettyCash::STATUS_POSTED) {
            session()->flash('error', 'Cannot delete posted transactions.');
            return;
        }

        $transaction->delete();
        session()->flash('message', 'Transaction deleted successfully.');
    }

    public function post($id)
    {
        $transaction = PettyCash::findOrFail($id);
        $transaction->post();
        session()->flash('message', 'Transaction posted successfully.');
    }

    public function void($id)
    {
        $transaction = PettyCash::findOrFail($id);
        $transaction->void();
        session()->flash('message', 'Transaction voided successfully.');
    }

    public function resetForm()
    {
        $this->reset(['account_id', 'amount', 'transaction_type', 'reference', 'description', 'editingId']);
        $this->transaction_date = now()->format('Y-m-d');
        $this->showForm = false;
    }

    public function with()
    {
        $query = PettyCash::with(['account', 'organization'])
            ->where('organization_id', $this->selectedOrganizationId);

        // Apply filters
        if ($this->search) {
            $query->where(function($q) {
                $q->where('reference', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('account', function($subq) {
                      $subq->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->status_filter) {
            $query->where('status', $this->status_filter);
        }

        if ($this->date_from) {
            $query->whereDate('transaction_date', '>=', $this->date_from);
        }

        if ($this->date_to) {
            $query->whereDate('transaction_date', '<=', $this->date_to);
        }

        if ($this->account_filter) {
            $query->where('account_id', $this->account_filter);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
                             ->orderBy('created_at', 'desc')
                             ->paginate(15);

        // Get accounts for dropdowns
        $accounts = Account::where('organization_id', $this->selectedOrganizationId)
                          ->where('is_active', true)
                          ->where('type', 'expense')
                          ->orderBy('account_number')
                          ->get();

        return [
            'transactions' => $transactions,
            'accounts' => $accounts,
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <div class="flex items-center space-x-2">
                    <flux:icon name="banknotes" class="h-10 w-10" />
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-slate-100">Petty Cash
                        Management</h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Manage daily petty cash transactions</p>
            </div>

            <!-- Right: Organization Selector & Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <div class="flex items-center space-x-3">
                    <flux:select wire:model.live="selectedOrganizationId" placeholder="Select Organization"
                        class="min-w-[200px]">
                        @foreach($organizations as $organization)
                        <flux:select.option value="{{ $organization->id }}">{{ $organization->name }}
                        </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:button wire:click="$set('showForm', true)" variant="primary" class="flex items-center">
                        <span>New Transaction</span>
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <x-glass-card colorScheme="indigo" class="backdrop-blur-sm mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
            <div class="flex items-center space-x-2 mb-4 md:mb-0">
                <flux:input type="text" wire:model.live.debounce.300ms="search"
                    class="block w-full rounded-lg border-0 focus:ring-2 focus:ring-indigo-500"
                    placeholder="Search transactions...">
                </flux:input>
            </div>
            <div class="flex items-center space-x-4">
                <flux:select wire:model.live="status_filter"
                    class="rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500">
                    <flux:select.option value="">All Statuses</flux:select.option>
                    <flux:select.option value="draft">Draft</flux:select.option>
                    <flux:select.option value="posted">Posted</flux:select.option>
                    <flux:select.option value="void">Void</flux:select.option>
                </flux:select>

                <div class="flex items-center space-x-2">
                    <flux:input type="date" wire:model.live="date_from"
                        class="rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500">
                    </flux:input>
                    <span class="text-indigo-300">to</span>
                    <flux:input type="date" wire:model.live="date_to"
                        class="rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500">
                    </flux:input>
                </div>

                <flux:button variant="ghost"
                    wire:click="$set('search', ''); $set('status_filter', ''); $set('account_filter', ''); $set('date_from', '{{ now()->startOfMonth()->format('Y-m-d') }}'); $set('date_to', '{{ now()->format('Y-m-d') }}')"
                    class="text-indigo-300 hover:text-indigo-100">
                    Clear Filters
                </flux:button>

                <span class="text-sm text-indigo-300">{{ $transactions->total() }}
                    Transaction{{ $transactions->total() != 1 ? 's' : '' }}</span>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg">
            <table class="min-w-full divide-y divide-indigo-200/20">
                <thead class="bg-gradient-to-r from-indigo-900/40 to-indigo-800/30 backdrop-blur-sm">
                    <tr>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                            Date
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                            Reference
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                            Account
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                            Description
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-indigo-100">
                            Debit
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-indigo-100">
                            Credit
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-indigo-100">
                            Status
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-indigo-100">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-indigo-200/10">
                    @forelse($transactions as $transaction)
                    <tr class="group hover:bg-indigo-900/20 backdrop-blur-sm transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-100">
                            {{ $transaction->transaction_date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-100">
                            {{ $transaction->reference ?: '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-100">
                            <div>
                                <div class="font-medium">{{ $transaction->account->name }}</div>
                                <div class="text-indigo-300 text-xs">{{ $transaction->account->account_number }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-indigo-100 max-w-xs truncate">
                            {{ $transaction->description }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            @if($transaction->debit > 0)
                            <span class="text-red-400 font-medium">{{ number_format($transaction->debit, 2) }}</span>
                            @else
                            <span class="text-indigo-300">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            @if($transaction->credit > 0)
                            <span class="text-emerald-400 font-medium">{{ number_format($transaction->credit, 2)
                                }}</span>
                            @else
                            <span class="text-indigo-300">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($transaction->status === 'draft')
                            <flux:badge color="amber" size="sm">Draft</flux:badge>
                            @elseif($transaction->status === 'posted')
                            <flux:badge color="emerald" size="sm">Posted</flux:badge>
                            @else
                            <flux:badge color="red" size="sm">Void</flux:badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <div class="flex justify-center space-x-2">
                                @if($transaction->status === 'draft')
                                <flux:button wire:click="edit({{ $transaction->id }})" variant="ghost" size="sm"
                                    class="text-indigo-300 hover:text-indigo-100">
                                    <flux:icon name="pencil" class="h-4 w-4" />
                                </flux:button>
                                <flux:button wire:click="post({{ $transaction->id }})" variant="ghost" size="sm"
                                    class="text-emerald-400 hover:text-emerald-300"
                                    onclick="return confirm('Are you sure you want to post this transaction?')">
                                    <flux:icon name="check" class="h-4 w-4" />
                                </flux:button>
                                <flux:button wire:click="delete({{ $transaction->id }})" variant="ghost" size="sm"
                                    class="text-red-400 hover:text-red-300"
                                    onclick="return confirm('Are you sure you want to delete this transaction?')">
                                    <flux:icon name="trash" class="h-4 w-4" />
                                </flux:button>
                                @elseif($transaction->status === 'posted')
                                <flux:button wire:click="void({{ $transaction->id }})" variant="ghost" size="sm"
                                    class="text-red-400 hover:text-red-300"
                                    onclick="return confirm('Are you sure you want to void this transaction?')">
                                    <flux:icon name="x-circle" class="h-4 w-4" />
                                </flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-indigo-300">
                            <flux:icon name="document-text" class="h-12 w-12 mx-auto mb-4 text-indigo-400" />
                            <p class="text-lg font-medium">No transactions found</p>
                            <p class="text-sm mt-1">Create your first petty cash transaction to get started.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-indigo-200/20">
            {{ $transactions->links() }}
        </div>
    </x-glass-card>

    <!-- Transaction Form Modal -->
    @if($showForm)
    <flux:modal wire:model="showForm" class="md:w-2xl">
        <flux:header>
            <flux:heading size="lg">{{ $editingId ? 'Edit' : 'New' }} Petty Cash Transaction</flux:heading>
        </flux:header>

        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <flux:select wire:model="account_id" label="Account" placeholder="Select Account" required>
                        @foreach($accounts as $account)
                        <flux:select.option value="{{ $account->id }}">{{ $account->account_number }} - {{
                            $account->name }}
                        </flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('account_id') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                <div>
                    <flux:select wire:model="transaction_type" label="Transaction Type" required>
                        <flux:select.option value="expense">Expense (Debit)</flux:select.option>
                        <flux:select.option value="income">Income (Credit)</flux:select.option>
                    </flux:select>
                    @error('transaction_type') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                <div>
                    <flux:input wire:model="amount" label="Amount" type="number" step="0.01" min="0" required />
                    @error('amount') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                <div>
                    <flux:input wire:model="transaction_date" label="Date" type="date" required />
                    @error('transaction_date') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                <div class="md:col-span-2">
                    <flux:input wire:model="reference" label="Reference" placeholder="Optional reference number" />
                    @error('reference') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                <div class="md:col-span-2">
                    <flux:textarea wire:model="description" label="Description" rows="3"
                        placeholder="Transaction description" required />
                    @error('description') <flux:error>{{ $message }}</flux:error> @enderror
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <flux:button wire:click="resetForm" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $editingId ? 'Update' : 'Create' }} Transaction
                </flux:button>
            </div>
        </form>
    </flux:modal>
    @endif

    <!-- Flash Messages -->
    @if (session()->has('message'))
    <flux:badge duration="3000" color="green">
        {{ session('message') }}
    </flux:badge>
    @endif
    @if (session()->has('error'))
    <flux:badge duration="3000" color="red">
        {{ session('error') }}
    </flux:badge>
    @endif

</div>