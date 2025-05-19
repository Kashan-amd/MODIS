<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Organization;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithPagination;

    public $from_organization_id = null;
    public $to_organization_id = null;
    public $amount = null;
    public $transaction_type = 'fund';
    public $editingTransactionId = null;
    public $isEditing = false;
    public $searchQuery = '';
    public $filterType = '';

    public function rules()
    {
        return [
            'from_organization_id' => 'required|exists:organizations,id',
            'to_organization_id' => 'required|exists:organizations,id|different:from_organization_id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_type' => 'required|in:fund,loan,return',
        ];
    }

    public function saveTransaction()
    {
        $this->validate();

        if ($this->isEditing) {
            $transaction = Transaction::find($this->editingTransactionId);
            $transaction->update([
                'from_organization_id' => $this->from_organization_id,
                'to_organization_id' => $this->to_organization_id,
                'amount' => $this->amount,
                'transaction_type' => $this->transaction_type,
            ]);

            $this->dispatch('transaction-updated', 'Transaction updated successfully');
        } else {
            Transaction::create([
                'from_organization_id' => $this->from_organization_id,
                'to_organization_id' => $this->to_organization_id,
                'amount' => $this->amount,
                'transaction_type' => $this->transaction_type,
            ]);

            $this->dispatch('transaction-created', 'Transaction created successfully');
        }

        $this->resetForm();
        $this->modal('transaction-form')->close();
    }

    public function editTransaction($transactionId)
    {
        $this->isEditing = true;
        $this->editingTransactionId = $transactionId;

        $transaction = Transaction::find($transactionId);
        $this->from_organization_id = $transaction->from_organization_id;
        $this->to_organization_id = $transaction->to_organization_id;
        $this->amount = $transaction->amount;
        $this->transaction_type = $transaction->transaction_type;
        $this->modal('transaction-form')->show();

    }

    public function deleteTransaction($transactionId)
    {
        Transaction::destroy($transactionId);
        $this->dispatch('transaction-deleted', 'Transaction deleted successfully');
    }

    public function resetForm()
    {
        $this->reset(['from_organization_id', 'to_organization_id', 'amount', 'transaction_type', 'editingTransactionId', 'isEditing']);
        $this->resetValidation();
    }

    public function cancelEdit()
    {
        $this->resetForm();
        $this->modal('transaction-form')->close();
    }

    public function with(): array
    {
        $query = Transaction::query()
            ->when($this->searchQuery, function ($query, $search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery
                        ->whereHas('fromOrganization', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('toOrganization', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        })
                        ->orWhere('amount', 'like', "%{$search}%");
                });
            })
            ->when($this->filterType, function ($query, $type) {
                $query->where('transaction_type', $type);
            })
            ->orderBy('transaction_date', 'desc');

        return [
            'transactions' => $query->paginate(10),
            'organizations' => Organization::orderBy('name')->get(),
            'totalFunds' => Transaction::where('transaction_type', 'fund')->sum('amount'),
            'totalLoans' => Transaction::where('transaction_type', 'loan')->sum('amount'),
            'totalReturns' => Transaction::where('transaction_type', 'return')->sum('amount'),
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
                    <flux:icon name="currency-dollar" class="h-10 w-10" />
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-slate-100">Transactions</h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Manage your transactions and their details</p>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Add transaction button -->
                <flux:modal.trigger name="transaction-form">
                    <flux:button variant="primary" class="flex items-center">
                        <flux:icon name="plus" class="h-4 w-4 mr-2" />
                        <span>New Transaction</span>
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
        <!-- Stats overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <x-glass-card colorScheme="emerald" class="backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-emerald-100">Total Funds</h3>
                        <p class="text-2xl font-bold mt-1">PKR {{ number_format($totalFunds, 2) }}</p>
                    </div>
                    <div class="bg-emerald-500/20 p-3 rounded-full">
                        <flux:icon name="banknotes" class="h-5 w-5 text-emerald-300" />
                    </div>
                </div>
            </x-glass-card>
            <x-glass-card colorScheme="amber" class="backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-amber-100">Total Loans</h3>
                        <p class="text-2xl font-bold mt-1">PKR {{ number_format($totalLoans, 2) }}</p>
                    </div>
                    <div class="bg-amber-500/20 p-3 rounded-full">
                        <flux:icon name="arrow-trending-up" class="h-5 w-5 text-amber-300" />
                    </div>
                </div>
            </x-glass-card>
            <x-glass-card colorScheme="purple" class="backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-purple-100">Total Returns</h3>
                        <p class="text-2xl font-bold mt-1">PKR {{ number_format($totalReturns, 2) }}</p>
                    </div>
                    <div class="bg-purple-500/20 p-3 rounded-full">
                        <flux:icon name="arrow-path" class="h-5 w-5 text-purple-300" />
                    </div>
                </div>
            </x-glass-card>
        </div>

        <!-- Transaction Form -->
        <flux:modal name="transaction-form" class="w-full max-w-4xl">
            <x-glass-card colorScheme="indigo" class="overflow-hidden">
                <form wire:submit="saveTransaction">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="from_organization_id" class="block text-sm font-medium ">From
                                Organization</label>
                            <flux:select id="from_organization_id" wire:model="from_organization_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <flux:select.option value="">Select organization</flux:select.option>
                                @foreach ($organizations as $organization)
                                <flux:select.option value="{{ $organization->id }}">{{ $organization->name }}
                                </flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('from_organization_id')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>


                        <div>
                            <label for="to_organization_id" class="block text-sm font-medium ">To
                                Organization</label>
                            <flux:select id="to_organization_id" wire:model="to_organization_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <flux:select.option value="">Select organization</flux:select.option>
                                @foreach ($organizations as $organization)
                                <flux:select.option value="{{ $organization->id }}">{{ $organization->name }}
                                </flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('to_organization_id')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="amount" class="block text-sm font-medium ">Amount</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <flux:input type="number" step="0.01" id="amount" wire:model="amount"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="0.00">
                                </flux:input>
                            </div>
                            @error('amount')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="transaction_type" class="block text-sm font-medium ">Transaction
                                Type</label>
                            <flux:select id="transaction_type" wire:model="transaction_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <flux:select.option value="fund">Fund</flux:select.option>
                                <flux:select.option value="loan">Loan</flux:select.option>
                                <flux:select.option value="return">Return</flux:select.option>
                            </flux:select>
                            @error('transaction_type')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        @if ($isEditing)
                        <flux:button type="button" variant="danger" wire:click="cancelEdit"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md   hover:bg-gray-50 mr-2">
                            Cancel
                        </flux:button>
                        @endif
                        <flux:button type="submit" variant="primary"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ $isEditing ? 'Update Transaction' : 'Create Transaction' }}
                        </flux:button>
                    </div>
                </form>
            </x-glass-card>
        </flux:modal>

        <!-- Transactions List -->
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">

                    <flux:input type="text" wire:model.live.debounce.300ms="searchQuery"
                        class="block w-full rounded-lg border-0 focus:ring-2 focus:ring-indigo-500"
                        placeholder="Search transactions...">
                    </flux:input>

                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <flux:select wire:model.live="filterType"
                            class="rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500 pr-8">
                            <flux:select.option value="">All Types</flux:select.option>
                            <flux:select.option value="fund">Fund</flux:select.option>
                            <flux:select.option value="loan">Loan</flux:select.option>
                            <flux:select.option value="return">Return</flux:select.option>
                        </flux:select>
                    </div>
                    <span class="text-sm text-indigo-300">{{ $transactions->total() }} {{ Str::plural('transaction',
                        $transactions->total()) }}</span>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg">
                <table class="min-w-full divide-y divide-indigo-200/20">
                    <thead class="bg-gradient-to-r from-indigo-900/40 to-indigo-800/30 backdrop-blur-sm">
                        <tr>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Date</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>From</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>To</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Amount</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Type</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center justify-end space-x-1">
                                    <span>Actions</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-indigo-900/10 backdrop-blur-sm divide-y divide-indigo-200/10">
                        @forelse ($transactions as $transaction)
                        <tr class="hover:bg-indigo-900/20 transition-colors duration-200">
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium">{{ $transaction->transaction_date->format('M d, Y') }}
                                </div>
                                <div class="text-xs text-slate-400">{{
                                    $transaction->transaction_date->format('h:i A') }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium">{{ $transaction->fromOrganization->name }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium">{{ $transaction->toOrganization->name }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="font-bold">
                                    <span class="text-slate-300">PKR</span> {{ number_format($transaction->amount, 2) }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($transaction->transaction_type === 'fund')
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-emerald-500/20 text-emerald-200">
                                    <flux:icon name="banknotes" class="inline-block h-3 w-3 mr-1" />
                                    Fund
                                </span>
                                @elseif($transaction->transaction_type === 'loan')
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-amber-500/20 text-amber-200">
                                    <flux:icon name="arrow-trending-up" class="inline-block h-3 w-3 mr-1" />
                                    Loan
                                </span>
                                @else
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-purple-500/20 text-purple-200">
                                    <flux:icon name="arrow-path" class="inline-block h-3 w-3 mr-1" />
                                    Return
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <flux:button variant="primary" size="xs"
                                        wire:click="editTransaction({{ $transaction->id }})" class="flex items-center">
                                        <span>Edit</span>
                                    </flux:button>
                                    <flux:button variant="danger" size="xs"
                                        wire:click="deleteTransaction({{ $transaction->id }})"
                                        wire:confirm="Are you sure you want to delete this transaction?"
                                        class="flex items-center">
                                        <span>Delete</span>
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div
                                    class="flex flex-col items-center justify-center p-6 bg-indigo-900/10 rounded-xl backdrop-blur-sm">
                                    <div
                                        class="w-20 h-20 rounded-full bg-indigo-900/20 flex items-center justify-center mb-4">
                                        <flux:icon name="currency-dollar"
                                            class="w-12 h-12 text-indigo-300 dark:text-indigo-400" />
                                    </div>
                                    <h3 class="text-xl font-medium text-slate-800 dark:text-slate-200">No transactions
                                        found</h3>
                                    <p class="mt-2 text-slate-500 dark:text-slate-400 max-w-sm">Get started by creating
                                        your first transaction using the "New Transaction" button above.</p>
                                    <flux:modal.trigger name="transaction-form" class="mt-4">
                                        <flux:button variant="primary" class="flex items-center">
                                            <flux:icon name="plus" class="h-4 w-4 mr-2" />
                                            Add Your First Transaction
                                        </flux:button>
                                    </flux:modal.trigger>
                                </div>
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

    </div>
</div>