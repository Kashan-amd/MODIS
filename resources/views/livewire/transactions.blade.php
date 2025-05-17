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
        <div class=" overflow-hidden shadow-xl sm:rounded-lg p-6">
            <!-- Stats overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-green-500 p-4 rounded-lg">
                    <h3 class="font-semibold text-green-200">Total Funds</h3>
                    <p class="text-2xl font-bold">PKR {{ number_format($totalFunds, 2) }}</p>
                </div>
                <div class="bg-blue-500 p-4 rounded-lg">
                    <h3 class="font-semibold text-blue-200">Total Loans</h3>
                    <p class="text-2xl font-bold">PKR {{ number_format($totalLoans, 2) }}</p>
                </div>
                <div class="bg-purple-500 p-4 rounded-lg">
                    <h3 class="font-semibold text-purple-200">Total Returns</h3>
                    <p class="text-2xl font-bold">PKR {{ number_format($totalReturns, 2) }}</p>
                </div>
            </div>

            <!-- Transaction Form -->
            <div class="mb-8 bg-zinc-900 card shadow p-4 rounded-lg">
                <h2 class="text-lg font-semibold mb-4">{{ $isEditing ? 'Edit Transaction' : 'New Transaction' }}</h2>
                <form wire:submit="saveTransaction">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="from_organization_id" class="block text-sm font-medium text-gray-700">From
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
                            <label for="to_organization_id" class="block text-sm font-medium text-gray-700">To
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
                            <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
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
                            <label for="transaction_type" class="block text-sm font-medium text-gray-700">Transaction
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
                            <flux:button type="button" wire:click="cancelEdit"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700  hover:bg-gray-50 mr-2">
                                Cancel
                            </flux:button>
                        @endif
                        <flux:button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ $isEditing ? 'Update Transaction' : 'Create Transaction' }}
                        </flux:button>
                    </div>
                </form>
            </div>

            <!-- Transactions List -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">Transaction History</h2>
                    <div class="flex space-x-2">
                        <div class="relative rounded-md shadow-sm">
                            <flux:input type="text" wire:model.live.debounce.300ms="searchQuery"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Search transactions...">
                            </flux:input>
                        </div>
                        <flux:select wire:model.live="filterType"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <flux:select.option value="">All Types</flux:select.option>
                            <flux:select.option value="fund">Fund</flux:select.option>
                            <flux:select.option value="loan">Loan</flux:select.option>
                            <flux:select.option value="return">Return</flux:select.option>
                        </flux:select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-zinc-900">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">
                                    Date</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">
                                    From</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">
                                    To</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">
                                    Amount</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">
                                    Type</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium  uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class=" divide-y divide-gray-200">
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm ">
                                        {{ $transaction->transaction_date->format('M d, Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm ">
                                        {{ $transaction->fromOrganization->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm ">
                                        {{ $transaction->toOrganization->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm ">
                                        PKR {{ number_format($transaction->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            @if ($transaction->transaction_type === 'fund') bg-green-700 text-green-800
                                            @elseif($transaction->transaction_type === 'loan') bg-blue-700 text-white
                                            @else bg-purple-500 text-white @endif">
                                            {{ ucfirst($transaction->transaction_type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <flux:button wire:click="editTransaction({{ $transaction->id }})"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            Edit
                                        </flux:button>
                                        <flux:button wire:click="deleteTransaction({{ $transaction->id }})"
                                            wire:confirm="Are you sure you want to delete this transaction?"
                                            class="text-red-600 hover:text-red-900">
                                            Delete
                                        </flux:button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm  text-center">
                                        No transactions found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
