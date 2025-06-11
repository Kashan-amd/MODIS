<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Organization;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

new class extends Component {
    // Report Settings
    public $organization_id = '';
    public $report_type = 'trial-balance'; // Default to Trial Balance
    public $start_date;
    public $end_date;
    public $report_data;

    public function mount()
    {
        // Set default dates (current month)
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->format('Y-m-d');

        // Set default organization if only one exists
        $organization = Organization::first();
        if ($organization) {
            $this->organization_id = $organization->id;
        }
    }

    public function generateReport()
    {
        $reportType = $this->report_type;

        if ($reportType === 'income-statement') {
            $this->report_data = $this->generateIncomeStatement();
        } elseif ($reportType === 'balance-sheet') {
            $this->report_data = $this->generateBalanceSheet();
        } elseif ($reportType === 'trial-balance') {
            $this->report_data = $this->generateTrialBalance();
        }
    }

    protected function generateIncomeStatement()
    {
        // Get income accounts
        $income = Account::where('type', 'income')->where('organization_id', $this->organization_id)->where('is_active', true)->get();

        // Get expense accounts
        $expenses = Account::where('type', 'expense')->where('organization_id', $this->organization_id)->where('is_active', true)->get();

        // Calculate income for the period
        $incomeItems = [];
        $totalIncome = 0;

        foreach ($income as $account) {
            $amount = $this->getAccountActivity($account->id);
            $incomeItems[] = [
                'account_number' => $account->account_number,
                'name' => $account->name,
                'amount' => abs($amount), // Income is typically stored as negative (credit balance)
            ];
            $totalIncome += abs($amount);
        }

        // Calculate expenses for the period
        $expenseItems = [];
        $totalExpenses = 0;

        foreach ($expenses as $account) {
            $amount = $this->getAccountActivity($account->id);
            $expenseItems[] = [
                'account_number' => $account->account_number,
                'name' => $account->name,
                'amount' => $amount,
            ];
            $totalExpenses += $amount;
        }

        // Calculate net income
        $netIncome = $totalIncome - $totalExpenses;

        return [
            'title' => 'Income Statement',
            'period' => Carbon::parse($this->start_date)->format('M d, Y') . ' to ' . Carbon::parse($this->end_date)->format('M d, Y'),
            'income' => $incomeItems,
            'total_income' => $totalIncome,
            'expenses' => $expenseItems,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
        ];
    }

    protected function generateBalanceSheet()
    {
        // Get asset accounts
        $assets = Account::where('type', 'asset')->where('organization_id', $this->organization_id)->where('is_active', true)->get();

        // Get liability accounts
        $liabilities = Account::where('type', 'liability')->where('organization_id', $this->organization_id)->where('is_active', true)->get();

        // Get equity accounts
        $equity = Account::where('type', 'equity')->where('organization_id', $this->organization_id)->where('is_active', true)->get();

        // Calculate assets
        $assetItems = [];
        $totalAssets = 0;

        foreach ($assets as $account) {
            $balance = $account->current_balance;
            $assetItems[] = [
                'account_number' => $account->account_number,
                'name' => $account->name,
                'balance' => $balance,
            ];
            $totalAssets += $balance;
        }

        // Calculate liabilities
        $liabilityItems = [];
        $totalLiabilities = 0;

        foreach ($liabilities as $account) {
            $balance = $account->current_balance;
            $liabilityItems[] = [
                'account_number' => $account->account_number,
                'name' => $account->name,
                'balance' => $balance,
            ];
            $totalLiabilities += $balance;
        }

        // Calculate equity
        $equityItems = [];
        $totalEquity = 0;

        foreach ($equity as $account) {
            $balance = $account->current_balance;
            $equityItems[] = [
                'account_number' => $account->account_number,
                'name' => $account->name,
                'balance' => $balance,
            ];
            $totalEquity += $balance;
        }

        // Calculate total liabilities and equity
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;

        return [
            'title' => 'Balance Sheet',
            'as_of' => Carbon::parse($this->end_date)->format('M d, Y'),
            'assets' => $assetItems,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilityItems,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equityItems,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
        ];
    }

    protected function generateTrialBalance()
    {
        // Get all accounts
        $accounts = Account::where('organization_id', $this->organization_id)->where('is_active', true)->orderBy('account_number')->get();

        $trialBalanceItems = [];
        $totalDebits = 0;
        $totalCredits = 0;
        $totalOpeningBalance = 0;
        $totalClosingBalance = 0;

        foreach ($accounts as $account) {
            // Get opening balance at start date
            $openingBalance = $this->getAccountBalance($account->id, $this->start_date);
            $totalOpeningBalance += $openingBalance;

            // Get activity during the period (debit/credit)
            $activity = $this->getAccountActivity($account->id);

            // Calculate closing balance using end date
            $closingBalance = $this->getAccountBalance($account->id, $this->end_date);
            $totalClosingBalance += $closingBalance;

            // For trial balance, we show absolute values in debit/credit columns
            $debit = 0;
            $credit = 0;

            if (in_array($account->type, ['asset', 'expense'])) {
                // Assets and expenses typically have debit balances
                if ($activity > 0) {
                    $debit = $activity;
                } else {
                    $credit = abs($activity);
                }
            } else {
                // Liabilities, equity, and income typically have credit balances
                if ($activity > 0) {
                    $credit = $activity;
                } else {
                    $debit = abs($activity);
                }
            }

            $trialBalanceItems[] = [
                'account_number' => $account->account_number,
                'name' => $account->name,
                'type' => $account->type,
                'opening_balance' => $openingBalance,
                'debit' => $debit,
                'credit' => $credit,
                'closing_balance' => $closingBalance,
            ];

            $totalDebits += $debit;
            $totalCredits += $credit;
        }

        return [
            'title' => 'Trial Balance',
            'as_of' => Carbon::parse($this->end_date)->format('M d, Y'),
            'accounts' => $trialBalanceItems,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'total_opening_balance' => $totalOpeningBalance,
            'total_closing_balance' => $totalClosingBalance,
        ];
    }

    protected function getAccountActivity($accountId)
    {
        // Get the sum of all transactions for the account within the date range
        return Transaction::join('transaction_entries', 'transactions.id', '=', 'transaction_entries.transaction_id')
            ->where('transaction_entries.account_id', $accountId)
            ->whereBetween('transactions.date', [$this->start_date, $this->end_date])
            ->sum('transaction_entries.amount');
    }

    protected function getAccountBalance($accountId, $date)
    {
        $account = Account::find($accountId);

        if (!$account) {
            return 0;
        }

        // Get the opening balance
        $openingBalance = $account->opening_balance;

        // Add all transactions up to the specified date
        $transactions = Transaction::join('transaction_entries', 'transactions.id', '=', 'transaction_entries.transaction_id')->where('transaction_entries.account_id', $accountId)->where('transactions.date', '<=', $date)->sum('transaction_entries.amount');

        return $openingBalance + $transactions;
    }

    // Auto format negative values with parenthesis
    public function formatAmount($amount, $decimals = 2)
    {
        if ($amount < 0) {
            return '(' . number_format(abs($amount), $decimals) . ')';
        }
        return number_format($amount, $decimals);
    }

    public function with(): array
    {
        return [
            'organizations' => Organization::orderBy('name')->get(),
            'reportTypes' => [
                'income-statement' => 'Income Statement',
                'balance-sheet' => 'Balance Sheet',
                'trial-balance' => 'Trial Balance',
            ],
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
                    <flux:icon name="document-chart-bar" class="h-10 w-10" />
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-slate-100">Financial Reports
                    </h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Generate financial statements and reports</p>
            </div>
        </div>

        <!-- Report Parameters -->
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm mb-8">
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-indigo-100">Report Parameters</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label for="organization_id" class="block text-sm font-medium">Organization</label>
                        <flux:select id="organization_id" wire:model="organization_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <flux:select.option value="">Select Organization</flux:select.option>
                            @foreach ($organizations as $organization)
                            <flux:select.option value="{{ $organization->id }}">{{ $organization->name }}
                            </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div>
                        <label for="report_type" class="block text-sm font-medium">Report Type</label>
                        <flux:select id="report_type" wire:model="report_type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($reportTypes as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div>
                        <label for="start_date" class="block text-sm font-medium">Start Date</label>
                        <flux:input id="start_date" type="date" wire:model="start_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </flux:input>
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium">End Date</label>
                        <flux:input id="end_date" type="date" wire:model="end_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </flux:input>
                    </div>
                </div>

                <div class="flex justify-end">
                    <flux:button wire:click="generateReport" variant="primary" class="inline-flex items-center">
                        <span>Generate Report</span>
                    </flux:button>
                </div>
            </div>
        </x-glass-card>

        <!-- Report Results -->
        @if ($report_data)
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm">
            <!-- Report Header -->
            <div class="text-center mb-6 border-b border-indigo-200/20 pb-4">
                <h2 class="text-2xl font-bold text-indigo-100">{{ $report_data['title'] }}</h2>
                @if (isset($report_data['period']))
                <p class="text-indigo-300 mt-1">{{ $report_data['period'] }}</p>
                @elseif(isset($report_data['as_of']))
                <p class="text-indigo-300 mt-1">As of {{ $report_data['as_of'] }}</p>
                @endif
            </div>

            <!-- Income Statement -->
            @if ($report_type === 'income-statement')
            <div class="space-y-6">
                <!-- Income Section -->
                <div>
                    <h3 class="text-lg font-semibold text-indigo-100 mb-2">Income</h3>
                    <div class="bg-indigo-900/20 rounded-lg">
                        <table class="min-w-full divide-y divide-indigo-200/20">
                            <thead class="bg-indigo-900/30">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                        Account</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                        Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-indigo-200/10">
                                @forelse($report_data['income'] as $item)
                                <tr class="hover:bg-indigo-900/20">
                                    <td class="px-6 py-4 text-sm text-indigo-300">
                                        {{ $item['account_number'] }} - {{ $item['name'] }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-indigo-300">
                                        {{ number_format($item['amount'], 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-4 text-sm text-center text-indigo-300">No income
                                        accounts found.</td>
                                </tr>
                                @endforelse
                                <tr class="bg-indigo-900/40">
                                    <td class="px-6 py-3 text-sm font-semibold text-indigo-100">Total Income
                                    </td>
                                    <td class="px-6 py-3 text-sm font-semibold text-right text-indigo-100">
                                        {{ number_format($report_data['total_income'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Expenses Section -->
                <div>
                    <h3 class="text-lg font-semibold text-indigo-100 mb-2">Expenses</h3>
                    <div class="bg-indigo-900/20 rounded-lg">
                        <table class="min-w-full divide-y divide-indigo-200/20">
                            <thead class="bg-indigo-900/30">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                        Account</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                        Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-indigo-200/10">
                                @forelse($report_data['expenses'] as $item)
                                <tr class="hover:bg-indigo-900/20">
                                    <td class="px-6 py-4 text-sm text-indigo-300">
                                        {{ $item['account_number'] }} - {{ $item['name'] }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-indigo-300">
                                        {{ number_format($item['amount'], 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-4 text-sm text-center text-indigo-300">No expense
                                        accounts found.</td>
                                </tr>
                                @endforelse
                                <tr class="bg-indigo-900/40">
                                    <td class="px-6 py-3 text-sm font-semibold text-indigo-100">Total Expenses
                                    </td>
                                    <td class="px-6 py-3 text-sm font-semibold text-right text-indigo-100">
                                        {{ number_format($report_data['total_expenses'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Net Income -->
                <div class="bg-indigo-900/30 p-4 rounded-lg">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-indigo-100">Net Income</h3>
                        <span
                            class="text-xl font-bold {{ $report_data['net_income'] >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                            {{ number_format($report_data['net_income'], 2) }}
                        </span>
                    </div>
                </div>
            </div>
            <!-- Balance Sheet -->
            @elseif($report_type === 'balance-sheet')
            <div class="space-y-6">
                <!-- Assets Section -->
                <div>
                    <h3 class="text-lg font-semibold text-indigo-100 mb-2">Assets</h3>
                    <div class="bg-indigo-900/20 rounded-lg">
                        <table class="min-w-full divide-y divide-indigo-200/20">
                            <thead class="bg-indigo-900/30">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                        Account</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                        Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-indigo-200/10">
                                @forelse($report_data['assets'] as $item)
                                <tr class="hover:bg-indigo-900/20">
                                    <td class="px-6 py-4 text-sm text-indigo-300">
                                        {{ $item['account_number'] }} - {{ $item['name'] }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-indigo-300">
                                        {{ number_format($item['balance'], 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-4 text-sm text-center text-indigo-300">No asset
                                        accounts found.</td>
                                </tr>
                                @endforelse
                                <tr class="bg-indigo-900/40">
                                    <td class="px-6 py-3 text-sm font-semibold text-indigo-100">Total Assets
                                    </td>
                                    <td class="px-6 py-3 text-sm font-semibold text-right text-indigo-100">
                                        {{ number_format($report_data['total_assets'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Liabilities Section -->
                <div>
                    <h3 class="text-lg font-semibold text-indigo-100 mb-2">Liabilities</h3>
                    <div class="bg-indigo-900/20 rounded-lg">
                        <table class="min-w-full divide-y divide-indigo-200/20">
                            <thead class="bg-indigo-900/30">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                        Account</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                        Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-indigo-200/10">
                                @forelse($report_data['liabilities'] as $item)
                                <tr class="hover:bg-indigo-900/20">
                                    <td class="px-6 py-4 text-sm text-indigo-300">
                                        {{ $item['account_number'] }} - {{ $item['name'] }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-indigo-300">
                                        {{ number_format($item['balance'], 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-4 text-sm text-center text-indigo-300">No liability
                                        accounts found.</td>
                                </tr>
                                @endforelse
                                <tr class="bg-indigo-900/40">
                                    <td class="px-6 py-3 text-sm font-semibold text-indigo-100">Total
                                        Liabilities</td>
                                    <td class="px-6 py-3 text-sm font-semibold text-right text-indigo-100">
                                        {{ number_format($report_data['total_liabilities'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Equity Section -->
                <div>
                    <h3 class="text-lg font-semibold text-indigo-100 mb-2">Equity</h3>
                    <div class="bg-indigo-900/20 rounded-lg">
                        <table class="min-w-full divide-y divide-indigo-200/20">
                            <thead class="bg-indigo-900/30">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                        Account</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                        Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-indigo-200/10">
                                @forelse($report_data['equity'] as $item)
                                <tr class="hover:bg-indigo-900/20">
                                    <td class="px-6 py-4 text-sm text-indigo-300">
                                        {{ $item['account_number'] }} - {{ $item['name'] }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-indigo-300">
                                        {{ number_format($item['balance'], 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-4 text-sm text-center text-indigo-300">No equity
                                        accounts found.</td>
                                </tr>
                                @endforelse
                                <tr class="bg-indigo-900/40">
                                    <td class="px-6 py-3 text-sm font-semibold text-indigo-100">Total Equity
                                    </td>
                                    <td class="px-6 py-3 text-sm font-semibold text-right text-indigo-100">
                                        {{ number_format($report_data['total_equity'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Total Liabilities and Equity -->
                <div class="bg-indigo-900/30 p-4 rounded-lg">
                    <div class="flex justify-between items-center">
                        {{-- <h3 class="text-lg font-bold text-indigo-100">Total Liabilities and Equity</h3> --}}
                        <h3 class="text-lg font-bold text-indigo-100">Total</h3>
                        <span class="text-xl font-bold text-indigo-100">
                            {{ number_format($report_data['total_liabilities_and_equity'], 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Trial Balance -->
            @elseif($report_type === 'trial-balance')
            <div class="space-y-6">
                <!-- Trial Balance Table -->
                <div class="bg-indigo-900/20 rounded-lg">
                    <table class="min-w-full divide-y divide-indigo-200/20">
                        <thead class="bg-indigo-900/30">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                    Account</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                    Type</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                    Opening Balance</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                    Debit</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                    Credit</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-indigo-200">
                                    Closing Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-indigo-200/10">
                            @foreach ($report_data['accounts'] as $item)
                            <tr class="hover:bg-indigo-900/20">
                                <td class="px-6 py-4 text-sm text-indigo-300">
                                    {{ $item['account_number'] }} - {{ $item['name'] }}</td>
                                <td class="px-6 py-4 text-sm text-indigo-300 capitalize">
                                    {{ $item['type'] }}</td>
                                <td class="px-6 py-4 text-sm text-right text-indigo-300">
                                    @if (isset($item['opening_balance']))
                                    {{ $this->formatAmount($item['opening_balance']) }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-indigo-300">
                                    @if ($item['debit'] > 0)
                                    {{ $this->formatAmount($item['debit']) }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-indigo-300">
                                    @if ($item['credit'] > 0)
                                    {{ $this->formatAmount($item['credit']) }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-indigo-300">
                                    @if (isset($item['closing_balance']))
                                    {{ $this->formatAmount($item['closing_balance']) }}
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            <tr class="bg-indigo-900/40">
                                <td colspan="2" class="px-6 py-3 text-sm font-semibold text-indigo-100">
                                    Totals</td>
                                <td class="px-6 py-3 text-sm font-semibold text-right text-indigo-100">
                                    @if (isset($report_data['total_opening_balance']))
                                    {{ $this->formatAmount($report_data['total_opening_balance']) }}
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm font-semibold text-right text-indigo-100">
                                    {{ $this->formatAmount($report_data['total_debits']) }}</td>
                                <td class="px-6 py-3 text-sm font-semibold text-right text-indigo-100">
                                    {{ $this->formatAmount($report_data['total_credits']) }}</td>
                                <td class="px-6 py-3 text-sm font-semibold text-right text-indigo-100">
                                    @if (isset($report_data['total_closing_balance']))
                                    {{ $this->formatAmount($report_data['total_closing_balance']) }}
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </x-glass-card>
        @endif
    </div>
</div>