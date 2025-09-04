<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Organization;
use App\Models\OpeningBalance;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithPagination;

    public $searchQuery = '';
    public $filterType = '';
    public $showLedger = false;

    public function getLedgerData()
    {
        $organizations = Organization::all();
        $ledgerData = [];

        foreach ($organizations as $organization) {
            // Get opening balance for the organization
            $openingBalance = OpeningBalance::where('organization_id', $organization->id)->first();
            $opening = [
                'amount' => $openingBalance ? $openingBalance->amount : 0,
                'type' => $openingBalance ? $openingBalance->type : null, // 'credit' or 'debit'
            ];

            $sent = [
                'fund' => Transaction::where('from_organization_id', $organization->id)->where('transaction_type', 'fund')->sum('amount'),
                'loan' => Transaction::where('from_organization_id', $organization->id)->where('transaction_type', 'loan')->sum('amount'),
                'return' => Transaction::where('from_organization_id', $organization->id)->where('transaction_type', 'return')->sum('amount'),
                'total' => Transaction::where('from_organization_id', $organization->id)->sum('amount'),
            ];

            $received = [
                'fund' => Transaction::where('to_organization_id', $organization->id)->where('transaction_type', 'fund')->sum('amount'),
                'loan' => Transaction::where('to_organization_id', $organization->id)->where('transaction_type', 'loan')->sum('amount'),
                'return' => Transaction::where('to_organization_id', $organization->id)->where('transaction_type', 'return')->sum('amount'),
                'total' => Transaction::where('to_organization_id', $organization->id)->sum('amount'),
            ];

            // Calculate balance including opening balance
            $transactionBalance = $received['total'] - $sent['total'];
            $finalBalance = $transactionBalance;

            // Adjust balance based on opening balance type
            if ($opening['type'] === 'credit') {
                $finalBalance += $opening['amount'];
            } elseif ($opening['type'] === 'debit') {
                $finalBalance -= $opening['amount'];
            }

            $ledgerData[$organization->id] = [
                'organization' => $organization,
                'opening' => $opening,
                'sent' => $sent,
                'received' => $received,
                'transaction_balance' => $transactionBalance,
                'balance' => $finalBalance,
            ];
        }

        return $ledgerData;
    }
    // fetch specific organization sum (load, return, fund)
    public function getOrganizationSum($organizationId, $type)
    {
        return Transaction::where('from_organization_id', $organizationId)->where('transaction_type', $type)->sum('amount');
    }

    public function toggleLedger()
    {
        $this->showLedger = !$this->showLedger;
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
                        ->orWhereHas('organization', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('entries', function ($q) use ($search) {
                            $q->whereHas('account', function ($aq) use ($search) {
                                $aq->where('name', 'like', "%{$search}%")
                                    ->orWhere('account_number', 'like', "%{$search}%")
                                    ->orWhere('description', 'like', "%{$search}%");
                            });
                        })
                        ->orWhereHas('jobBooking', function ($q) use ($search) {
                            $q->where('job_number', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%");
                        })
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('amount', 'like', "%{$search}%");
                });
            })
            ->when($this->filterType, function ($query, $type) {
                $query->where('transaction_type', $type);
            })
            ->orderBy(DB::raw('COALESCE(date, transaction_date)'), 'desc');

        // Get all transactions (for both traditional transfers and journal entries)
        $transactions = $query->paginate(10);

        return [
            'transactions' => $transactions,
            'organizations' => Organization::orderBy('name')->get(),
            'totalFunds' => Transaction::where('transaction_type', 'fund')->sum('amount'),
            'totalLoans' => Transaction::where('transaction_type', 'loan')->sum('amount'),
            'totalReturns' => Transaction::where('transaction_type', 'return')->sum('amount'),
            'ledgerData' => $this->getLedgerData(),
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
                <p class="text-slate-500 dark:text-slate-400 mt-1">View all financial transactions across
                    organizations</p>
            </div>

            <!-- Right: Actions -->
            {{-- <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Link to journal entries -->
                <a href="{{ route('accounts.journal-entries') }}">
                    <flux:button variant="primary" class="flex items-center">
                        <flux:icon name="document-text" class="h-4 w-4 mr-2" />
                        <span>Create New Journal Entry</span>
                    </flux:button>
                </a>
            </div> --}}
        </div>
        <!-- Stats overview -->
        {{-- <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <x-glass-card colorScheme="indigo" class="backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-indigo-100">Transaction Summary</h3>
                        <p class="text-2xl font-bold mt-1">{{ $transactions->total() }} Records</p>
                    </div>
                    <div class="bg-indigo-500/20 p-3 rounded-full">
                        <flux:icon name="document-chart-bar" class="h-5 w-5 text-indigo-300" />
                    </div>
                </div>
            </x-glass-card>

            <x-glass-card colorScheme="emerald" class="backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-emerald-100">Inter-Organization</h3>
                        <div class="mt-1 flex items-end space-x-2">
                            <p class="text-2xl font-bold">PKR
                                {{ number_format($totalFunds + $totalLoans + $totalReturns, 2) }}</p>
                            <p class="text-xs text-emerald-300 mb-1">(Funds + Loans + Returns)</p>
                        </div>
                    </div>
                    <div class="bg-emerald-500/20 p-3 rounded-full">
                        <flux:icon name="arrows-right-left" class="h-5 w-5 text-emerald-300" />
                    </div>
                </div>
            </x-glass-card>

            <a href="{{ route('accounts.journal-entries') }}" class="block">
                <x-glass-card colorScheme="purple" class="backdrop-blur-sm hover:shadow-lg transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-purple-100">Create New Entry</h3>
                            <p class="text-sm mt-1 text-purple-200">Add a new transaction via journal entry</p>
                        </div>
                        <div class="bg-purple-500/20 p-3 rounded-full">
                            <flux:icon name="plus" class="h-5 w-5 text-purple-300" />
                        </div>
                    </div>
                </x-glass-card>
            </a>
        </div> --}}

        <!-- Transactions List -->
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">

                    <flux:input type="text" wire:model.live.debounce.300ms="searchQuery"
                        class="block w-full rounded-lg border-0 focus:ring-2 focus:ring-indigo-500"
                        placeholder="Search ...">
                    </flux:input>

                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <flux:select wire:model.live="filterType"
                            class="rounded-lg border-0 bg-indigo-900/10 focus:ring-2 focus:ring-indigo-500 pr-8">
                            <flux:select.option value="">All Transaction Types</flux:select.option>
                            <flux:select.option value="fund">Fund Transfer</flux:select.option>
                            <flux:select.option value="loan">Loan</flux:select.option>
                            <flux:select.option value="return">Return</flux:select.option>
                            <flux:select.option value="invoice">Invoice</flux:select.option>
                            <flux:select.option value="payment">Payment</flux:select.option>
                            <flux:select.option value="expense">Expense</flux:select.option>
                            <flux:select.option value="revenue">Revenue</flux:select.option>
                            <flux:select.option value="asset">Asset</flux:select.option>
                            <flux:select.option value="liability">Liability</flux:select.option>
                        </flux:select>
                    </div>
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
                                <div class="flex items-center space-x-1">
                                    <span>Date</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Reference</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Description</span>
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
                                <div class="font-medium">
                                    {{ $transaction->date
                                    ? $transaction->date->format('M d, Y')
                                    : ($transaction->transaction_date
                                    ? $transaction->transaction_date->format('M d, Y')
                                    : 'N/A') }}
                                </div>
                                <div class="text-xs text-slate-400">
                                    @if ($transaction->organization)
                                    {{ $transaction->organization->name }}
                                    @elseif($transaction->fromOrganization && $transaction->toOrganization)
                                    {{ $transaction->fromOrganization->name }} →
                                    {{ $transaction->toOrganization->name }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium">
                                    {{ $transaction->reference ?? 'TX' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT)
                                    }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium">
                                    {{ $transaction->description ??
                                    ($transaction->fromOrganization && $transaction->toOrganization
                                    ? 'Transfer: ' . $transaction->fromOrganization->name . ' to ' .
                                    $transaction->toOrganization->name
                                    : 'N/A') }}
                                </div>
                                @if ($transaction->jobBooking)
                                <div class="text-xs text-slate-400">
                                    Job: {{ $transaction->jobBooking->job_number }} -
                                    {{ $transaction->jobBooking->title }}
                                </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="font-bold">
                                    <span class="text-slate-300">PKR</span>
                                    {{ number_format($transaction->amount, 2) }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if ($transaction->transaction_type === 'fund')
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-emerald-500/20 text-emerald-200">
                                    <flux:icon name="banknotes" class="inline-block h-3 w-3 mr-1" />
                                    Fund Transfer
                                </span>
                                @elseif($transaction->transaction_type === 'loan')
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-amber-500/20 text-amber-200">
                                    <flux:icon name="arrow-trending-up" class="inline-block h-3 w-3 mr-1" />
                                    Loan
                                </span>
                                @elseif($transaction->transaction_type === 'return')
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-purple-500/20 text-purple-200">
                                    <flux:icon name="arrow-path" class="inline-block h-3 w-3 mr-1" />
                                    Return
                                </span>
                                @elseif($transaction->transaction_type === 'invoice')
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-blue-500/20 text-blue-200">
                                    <flux:icon name="document-text" class="inline-block h-3 w-3 mr-1" />
                                    Invoice
                                </span>
                                @elseif($transaction->transaction_type === 'payment')
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-green-500/20 text-green-200">
                                    <flux:icon name="credit-card" class="inline-block h-3 w-3 mr-1" />
                                    Payment
                                </span>
                                @elseif($transaction->transaction_type === 'expense')
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-red-500/20 text-red-200">
                                    <flux:icon name="receipt-percent" class="inline-block h-3 w-3 mr-1" />
                                    Expense
                                </span>
                                @else
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-indigo-500/20 text-indigo-200">
                                    <flux:icon name="document-chart-bar" class="inline-block h-3 w-3 mr-1" />
                                    {{ $transaction->transaction_type ? ucfirst($transaction->transaction_type) :
                                    'Journal Entry' }}
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <a href="{{ route('accounts.journal-entries') }}?transaction_id={{ $transaction->id }}"
                                        class="flex items-center">
                                        <flux:button variant="primary" size="xs" class="flex items-center">
                                            <flux:icon name="document-text" class="h-3 w-3 mr-1" />
                                            <span>View Details</span>
                                        </flux:button>
                                    </a>
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
                                    <h3 class="text-xl font-medium text-slate-800 dark:text-slate-200">No
                                        transactions found</h3>
                                    <p class="mt-2 text-slate-500 dark:text-slate-400 max-w-sm">
                                        All financial transactions are created through the Journal Entries system.
                                    </p>
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

        <!-- Organization Ledger -->
        {{-- <div class="mt-8 flex justify-between items-center">
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center">
                <flux:icon name="clipboard-document-list" class="h-6 w-6 mr-2" />
                Organization Ledger
            </h2>
            <div class="flex items-center space-x-2">
                <a href="{{ route('accounts.transactions.print-ledger') }}" target="_blank">
                    <flux:button variant="primary" class="flex items-center">
                        <flux:icon name="document-arrow-down" class="h-4 w-4 mr-2" />
                        <span>Print PDF</span>
                    </flux:button>
                </a>
                <flux:button wire:click="toggleLedger" variant="primary" class="flex items-center">
                    <span>{{ $showLedger ? 'Hide Ledger' : 'Show Ledger' }}</span>
                </flux:button>
            </div>
        </div> --}}

        @if ($showLedger)
        <x-glass-card colorScheme="amber" class="mt-4">
            <div class="overflow-x-auto rounded-lg">
                <table class="min-w-full divide-y divide-purple-200/20">
                    <thead class="bg-gradient-to-r backdrop-blur-sm">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">
                                Organization
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider">
                                Type
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider">
                                Debit (Sent)
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider">
                                Credit (Received)
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider">
                                Balance
                            </th>
                        </tr>
                    </thead>
                    <tbody class="backdrop-blur-sm divide-y divide-purple-200/10">
                        @forelse ($ledgerData as $data)
                        <!-- Fund row -->
                        <tr class="hover:bg-zinc-900/20 transition-colors duration-200">
                            <td class="px-6 py-4 text-sm font-medium" rowspan="3">
                                {{ $data['organization']->name }}

                                {{-- show organization opening balance also --}}
                                @if ($data['opening']['amount'] > 0)
                                <div class="text-xs text-slate-400">
                                    Opening Balance: <br>
                                    <flux:badge color="green" size="sm">PKR
                                        {{ number_format($data['opening']['amount'], 2) }}
                                    </flux:badge>
                                    ({{ $data['opening']['type'] === 'credit' ? 'Credit' : 'Debit' }})
                                </div>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm text-center">
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-emerald-500/20 text-emerald-200">
                                    <flux:icon name="banknotes" class="inline-block h-3 w-3 mr-1" />
                                    Fund
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-center">
                                <span class="font-semibold text-emerald-300">
                                    PKR {{ number_format($data['sent']['fund'], 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-center">
                                <span class="font-semibold text-emerald-300">
                                    PKR {{ number_format($data['received']['fund'], 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-center">
                                <span
                                    class="font-semibold {{ $data['received']['fund'] - $data['sent']['fund'] >= 0 ? 'text-emerald-300' : 'text-red-400' }}">
                                    PKR
                                    {{ number_format($data['received']['fund'] - $data['sent']['fund'], 2) }}
                                </span>
                            </td>
                        </tr>

                        <!-- Loan row -->
                        <tr class="hover:bg-zinc-900/20 transition-colors duration-200">
                            <td class="px-6 py-3 text-sm text-center">
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-amber-500/20 text-amber-200">
                                    <flux:icon name="arrow-trending-up" class="inline-block h-3 w-3 mr-1" />
                                    Loan
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-center">
                                <span class="font-semibold text-amber-300">
                                    PKR {{ number_format($data['sent']['loan'], 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-center">
                                <span class="font-semibold text-amber-300">
                                    PKR {{ number_format($data['received']['loan'], 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-center">
                                <span
                                    class="font-semibold {{ $data['received']['loan'] - $data['sent']['loan'] >= 0 ? 'text-emerald-300' : 'text-red-400' }}">
                                    PKR
                                    {{ number_format($data['received']['loan'] - $data['sent']['loan'], 2) }}
                                </span>
                            </td>
                        </tr>

                        <!-- Return row -->
                        <tr class="hover:bg-zinc-900/20 transition-colors duration-200 border-b border-grey-200/10">
                            <td class="px-6 py-3 text-sm text-center">
                                <span class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-zinc-500/20">
                                    <flux:icon name="arrow-path" class="inline-block h-3 w-3 mr-1" />
                                    Return
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-center">
                                <span class="font-semibold text-purple-300">
                                    PKR {{ number_format($data['sent']['return'], 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-center">
                                <span class="font-semibold text-purple-300">
                                    PKR {{ number_format($data['received']['return'], 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-center">
                                <span
                                    class="font-semibold {{ $data['received']['return'] - $data['sent']['return'] >= 0 ? 'text-emerald-300' : 'text-red-400' }}">
                                    PKR
                                    {{ number_format($data['received']['return'] - $data['sent']['return'], 2) }}
                                </span>
                            </td>
                        </tr>
                        <!-- Total row -->
                        <tr class="bg-zinc-900 hover:bg-zinc-800 duration-200">
                            <td class="px-6 py-3 text-sm font-bold">
                                {{ $data['organization']->name }} Total
                            </td>
                            <td class="px-6 py-3 text-sm text-center font-bold">
                                All Types
                            </td>
                            <td class="px-6 py-3 text-sm text-center font-bold">
                                PKR {{ number_format($data['sent']['total'], 2) }}
                            </td>
                            <td class="px-6 py-3 text-sm text-center font-bold">
                                PKR {{ number_format($data['received']['total'], 2) }}
                            </td>
                            <td class="px-6 py-3 text-sm text-center">
                                <span
                                    class="font-bold {{ $data['balance'] >= 0 ? 'text-emerald-300' : 'text-red-400' }}">
                                    PKR {{ number_format($data['balance'], 2) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                No organization data available
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gradient-to-r from-zinc-900/50 to-zinc-800/40 backdrop-blur-sm">
                        <tr>
                            <th scope="row" class="px-6 py-3 text-left text-sm font-bold" colspan="2">
                                Grand Total (All Organizations)
                            </th>
                            <td class="px-6 py-3 text-sm text-center font-bold">
                                PKR {{ number_format($totalFunds + $totalLoans + $totalReturns, 2) }}
                            </td>
                            <td class="px-6 py-3 text-sm text-center font-bold">
                                PKR {{ number_format($totalFunds + $totalLoans + $totalReturns, 2) }}
                            </td>
                            <td class="px-6 py-3 text-sm text-center font-bold">
                                PKR 0.00
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-glass-card>
        @endif

    </div>
</div>