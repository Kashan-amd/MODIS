<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\Organization;
use Livewire\WithPagination;
use Illuminate\Support\Collection;

new class extends Component {
    use WithPagination;

    // Chart of Accounts fields
    public $account_number = '';
    public $name = '';
    public $type = '';
    public $description = '';
    public $is_active = true;
    public $opening_balance = 0.0;
    public $balance_date = '';
    public $organization_id = '';
    public $parent_id = null; // Add parent_id field
    public $is_parent = false; // Flag to indicate if the account is a parent account

    // Edit mode
    public $editingAccountId = null;
    public $isEditing = false;
    public $searchQuery = '';

    // Validation rules
    protected function rules()
    {
        $rules = [
            'account_number' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,income,expense',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'opening_balance' => 'nullable|numeric',
            'balance_date' => 'nullable|date',
            'organization_id' => 'required|exists:organizations,id',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
        ];

        // If parent_id is set, make sure is_parent is false
        if ($this->parent_id) {
            $rules['is_parent'] = 'accepted:0'; // Must be false
        }

        return $rules;
    }

    public function mount()
    {
        // Set default organization if only one exists
        $organization = Organization::first();
        if ($organization) {
            $this->organization_id = $organization->id;
        }

        // Set default date
        $this->balance_date = now()->format('Y-m-d');
    }

    public function saveAccount()
    {
        $this->validate();

        // Format account number if needed (for sub-accounts with just the head number)
        $parts = explode('-', $this->account_number);

        // If parent_id is set and account number doesn't have a dash, this is a sub-account
        // We need to automatically add the appropriate sequence number
        if ($this->parent_id && count($parts) === 1) {
            $headNumber = $this->account_number;

            // Find the maximum sequence number for this head number
            $maxSeq = Account::where('account_number', 'like', $headNumber . '-%')
                ->get()
                ->map(function ($account) use ($headNumber) {
                    $parts = explode('-', $account->account_number);
                    return count($parts) === 2 ? (int) $parts[1] : 0;
                })
                ->max();

            // If no existing sub-accounts, start with 1, otherwise increment
            $nextSeq = $maxSeq ? $maxSeq + 1 : 1;
            $this->account_number = $headNumber . '-' . $nextSeq;
        }

        // Determine level based on account number format or parent relationship
        $level = 0;
        $parts = explode('-', $this->account_number);
        if (count($parts) === 2 || $this->parent_id) {
            $level = 1; // This is a child account
            $this->is_parent = false; // Child accounts cannot be parents
        }

        // For head parent accounts, set organization_id to null
        $organizationId = $this->organization_id;
        if ($this->is_parent && $level === 0) {
            // Check if this is intended to be a head parent (accessible across orgs)
            // For this example, assume any parent account for org ID 1 is a head parent
            // You may want to add a dedicated checkbox for this in the UI
            if ($this->organization_id == 1) {
                // Default org ID
                $organizationId = null;
            }
        }

        $data = [
            'account_number' => $this->account_number,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'opening_balance' => $this->opening_balance,
            'current_balance' => $this->opening_balance,
            'balance_date' => $this->balance_date,
            'organization_id' => $organizationId,
            'level' => $level,
            'is_parent' => $this->is_parent && $level === 0, // Only base accounts can be parents
            'parent_id' => $this->parent_id,
        ];

        if ($this->isEditing) {
            $account = Account::find($this->editingAccountId);
            $account->update($data);
            $this->dispatch('account-updated', 'Account updated successfully');
        } else {
            $account = Account::create($data);

            // If this is a child account, make sure the parent is marked as a parent
            if ($this->parent_id) {
                $parent = Account::find($this->parent_id);
                if ($parent && !$parent->is_parent) {
                    $parent->update(['is_parent' => true]);
                }
            }

            $this->dispatch('account-created', 'Account created successfully');
        }

        $this->resetForm();
        $this->modal('account-form')->close();
    }

    public function editAccount($accountId)
    {
        $this->isEditing = true;
        $this->editingAccountId = $accountId;

        $account = Account::find($accountId);
        $this->account_number = $account->account_number;
        $this->name = $account->name;
        $this->type = $account->type;
        $this->description = $this->description;
        $this->is_active = $account->is_active;
        $this->opening_balance = $account->opening_balance;
        $this->balance_date = $account->balance_date ? $account->balance_date->format('Y-m-d') : now()->format('Y-m-d');
        $this->organization_id = $account->organization_id;
        $this->parent_id = $account->parent_id;

        $this->modal('account-form')->show();
    }

    public function deleteAccount($accountId)
    {
        $account = Account::find($accountId);

        if (!$account) {
            return;
        }

        // Check if account has related transactions
        if ($account->transactions()->count() > 0) {
            $this->dispatch('account-error', 'Cannot delete account with related transactions');
            return;
        }

        // Check if account has children
        if ($account->hasChildren()) {
            $this->dispatch('account-error', 'Cannot delete account with child accounts. Please delete child accounts first.');
            return;
        }

        // Update parent's is_parent flag if needed
        if ($account->parent_id) {
            $parent = Account::find($account->parent_id);
            if ($parent && $parent->children()->count() <= 1) {
                $parent->update(['is_parent' => false]);
            }
        }

        $account->delete();
        $this->dispatch('account-deleted', 'Account deleted successfully');
    }

    public function resetForm()
    {
        $this->reset(['account_number', 'name', 'type', 'description', 'is_active', 'opening_balance', 'editingAccountId', 'isEditing', 'parent_id', 'is_parent']);

        // Keep organization_id and set date to today
        $this->balance_date = now()->format('Y-m-d');

        $this->resetValidation();
    }

    public function cancelEdit()
    {
        $this->resetForm();
        $this->modal('account-form')->close();
    }

    public function with(): array
    {
        $query = Account::query()
            ->when($this->searchQuery, function ($query, $search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery
                        ->where('account_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('account_number')
            ->with(['parent', 'children']);

        return [
            'accounts' => $query->paginate(10),
            'organizations' => Organization::orderBy('name')->get(),
            'accountTypes' => Account::getTypes(),
            'accountSummary' => $this->getAccountSummary(),
        ];
    }

    protected function getAccountSummary(): Collection
    {
        $summary = collect();
        foreach (Account::getTypes() as $type => $label) {
            $total = Account::where('type', $type)->where('organization_id', $this->organization_id)->sum('current_balance');

            $summary->put($type, [
                'label' => $label,
                'total' => $total,
            ]);
        }
        return $summary;
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <div class="flex items-center space-x-2">
                    <flux:icon name="chart-bar-square" class="h-10 w-10" />
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-slate-100">Chart of Accounts
                    </h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Manage your organization's financial accounts</p>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Add account button -->
                <flux:modal.trigger name="account-form">
                    <flux:button variant="primary" class="flex items-center">
                        <flux:icon name="plus" class="h-4 w-4 mr-2" />
                        <span>New Account</span>
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- Financial Summary Cards -->
        {{-- <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
            <!-- Assets card -->
            <x-glass-card class="bg-emerald-900/10">
                <div class="flex flex-col h-full">
                    <div class="text-sm font-medium text-emerald-300">
                        {{ $accountSummary['asset']['label'] }}
                    </div>
                    <div class="text-2xl font-bold mt-1 text-emerald-100">
                        PKR {{ number_format($accountSummary['asset']['total'], 2) }}
                    </div>
                </div>
            </x-glass-card>

            <!-- Liabilities card -->
            <x-glass-card class="bg-red-900/10">
                <div class="flex flex-col h-full">
                    <div class="text-sm font-medium text-red-300">
                        {{ $accountSummary['liability']['label'] }}
                    </div>
                    <div class="text-2xl font-bold mt-1 text-red-100">
                        PKR {{ number_format($accountSummary['liability']['total'], 2) }}
                    </div>
                </div>
            </x-glass-card>

            <!-- Equity card -->
            <x-glass-card class="bg-blue-900/10">
                <div class="flex flex-col h-full">
                    <div class="text-sm font-medium text-blue-300">
                        Investment
                    </div>
                    <div class="text-2xl font-bold mt-1 text-blue-100">
                        PKR {{ number_format($accountSummary['equity']['total'], 2) }}
                    </div>
                </div>
            </x-glass-card>

            <!-- Income card -->
            <x-glass-card class="bg-amber-900/10">
                <div class="flex flex-col h-full">
                    <div class="text-sm font-medium text-amber-300">
                        {{ $accountSummary['income']['label'] }}
                    </div>
                    <div class="text-2xl font-bold mt-1 text-amber-100">
                        PKR {{ number_format($accountSummary['income']['total'], 2) }}
                    </div>
                </div>
            </x-glass-card>

            <!-- Expense card -->
            <x-glass-card class="bg-purple-900/10">
                <div class="flex flex-col h-full">
                    <div class="text-sm font-medium text-purple-300">
                        {{ $accountSummary['expense']['label'] }}
                    </div>
                    <div class="text-2xl font-bold mt-1 text-purple-100">
                        PKR {{ number_format($accountSummary['expense']['total'], 2) }}
                    </div>
                </div>
            </x-glass-card>
        </div> --}}

        <!-- Account Form Modal -->
        <flux:modal name="account-form" class="w-full max-w-4xl">
            <x-glass-card colorScheme="indigo" class="overflow-hidden">
                <form wire:submit.prevent="saveAccount">
                    <div class="space-y-6">
                        <div class="border-b border-indigo-200/20 pb-6">
                            <h3 class="text-lg font-medium text-indigo-100">
                                {{ $isEditing ? 'Edit Account' : 'Create New Account' }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="organization_id" class="block text-sm font-medium">Organization</label>
                                    <flux:select id="organization_id" wire:model="organization_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <flux:select.option value="">Select Organization</flux:select.option>
                                        @foreach ($organizations as $organization)
                                            <flux:select.option value="{{ $organization->id }}">
                                                {{ $organization->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @error('organization_id')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label for="account_number" class="block text-sm font-medium">Account Number
                                        <span class="text-xs text-gray-500">
                                            (Parent: 1000, Child: 1000-1)
                                        </span>
                                    </label>
                                    <flux:input id="account_number" type="text" wire:model="account_number"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Enter account number">
                                    </flux:input>
                                    @error('account_number')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium">Account Name</label>
                                    <flux:input id="name" type="text" wire:model="name"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Enter account name">
                                    </flux:input>
                                    @error('name')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label for="type" class="block text-sm font-medium">Account Type</label>
                                    <flux:select id="type" wire:model="type"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <flux:select.option value="">Select account type</flux:select.option>
                                        @foreach ($accountTypes as $value => $label)
                                            <flux:select.option value="{{ $value }}">{{ $label }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @error('type')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="opening_balance" class="block text-sm font-medium">Opening
                                        Balance</label>
                                    <flux:input id="opening_balance" type="number" step="0.01"
                                        wire:model="opening_balance"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="0.00">
                                    </flux:input>
                                    @error('opening_balance')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label for="balance_date" class="block text-sm font-medium">Balance Date</label>
                                    <flux:input id="balance_date" type="date" wire:model="balance_date"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </flux:input>
                                    @error('balance_date')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-4">
                                <label for="description" class="block text-sm font-medium">Description</label>
                                <flux:textarea id="description" wire:model="description" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Enter description">
                                </flux:textarea>
                                @error('description')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mt-4 flex items-center space-x-6">
                                <div class="flex items-center">
                                    <flux:checkbox id="is_active" wire:model="is_active" class="mr-2"></flux:checkbox>
                                    <label for="is_active" class="text-sm font-medium">Active Account</label>
                                </div>
                                <div class="flex items-center">
                                    <flux:checkbox id="is_parent" wire:model="is_parent" class="mr-2">
                                    </flux:checkbox>
                                    <label for="is_parent" class="text-sm font-medium">Parent Account</label>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label for="parent_id" class="block text-sm font-medium">Parent Account
                                    (optional)</label>
                                <flux:select id="parent_id" wire:model="parent_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <flux:select.option value="">No Parent (Main Account)</flux:select.option>
                                    @foreach (Account::where('is_parent', true)->where(function ($query) {
            $query->where('organization_id', $this->organization_id)->orWhereNull('organization_id'); // Include head parents
        })->orderBy('account_number')->get() as $parentAccount)
                                        <flux:select.option value="{{ $parentAccount->id }}">
                                            {{ $parentAccount->full_account_name }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('parent_id')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
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
                                    {{ $isEditing ? 'Update Account' : 'Create Account' }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </form>
            </x-glass-card>
        </flux:modal>

        <!-- Accounts List -->
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">
                    <flux:input type="text" wire:model.live.debounce.300ms="searchQuery"
                        class="block w-full rounded-lg border-0 focus:ring-2 focus:ring-indigo-500"
                        placeholder="Search accounts...">
                    </flux:input>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-indigo-300 mr-2">{{ $accounts->total() }}
                        {{ Str::plural('account', $accounts->total()) }}</span>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg">
                <table class="min-w-full divide-y divide-indigo-200/20">
                    <thead class="bg-gradient-to-r from-indigo-900/40 to-indigo-800/30 backdrop-blur-sm">
                        <tr>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Account Number</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Name</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Type</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Balance</span>
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
                        @forelse ($accounts as $account)
                            <tr class="hover:bg-indigo-900/20 transition-colors duration-200">
                                <td class="px-6 py-4 text-sm">
                                    <div class="font-medium text-indigo-100">
                                        @if ($account->level > 0)
                                            <span class="inline-block"
                                                style="margin-left: {{ $account->level * 20 }}px">
                                                <span class="text-indigo-400 mr-2">└─</span>
                                            </span>
                                        @endif
                                        {{ $account->formatted_account_number }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-indigo-200">
                                    @if ($account->level > 0)
                                        <span class="inline-block" style="margin-left: {{ $account->level * 20 }}px">
                                    @endif
                                    {{ $account->name }}
                                    @if ($account->is_parent)
                                        <span class="ml-2 text-xs text-indigo-400">(Parent)</span>
                                    @endif
                                    @if ($account->level > 0)
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span
                                        class="px-3 py-1 text-xs leading-5 font-semibold rounded-full
                                    {{ $account->type === 'asset'
                                        ? 'bg-emerald-500/20 text-emerald-200'
                                        : ($account->type === 'liability'
                                            ? 'bg-red-500/20 text-red-200'
                                            : ($account->type === 'equity'
                                                ? 'bg-blue-500/20 text-blue-200'
                                                : ($account->type === 'income'
                                                    ? 'bg-amber-500/20 text-amber-200'
                                                    : 'bg-purple-500/20 text-purple-200'))) }}">
                                        {{ $accountTypes[$account->type] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-indigo-200">
                                    <div class="font-medium">{{ $account->formatted_current_balance }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($account->is_active)
                                        <span
                                            class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-emerald-500/20 text-emerald-200">
                                            Active
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-slate-500/20 text-slate-200">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <div class="flex justify-end items-center space-x-2">
                                        <flux:button size="xs" variant="primary"
                                            wire:click="editAccount({{ $account->id }})">
                                            Edit
                                        </flux:button>

                                        <flux:button size="xs" variant="danger"
                                            wire:confirm="Are you sure you want to delete this account? This action cannot be undone if the account has transactions."
                                            wire:click="deleteAccount({{ $account->id }})">
                                            Delete
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
                                            <flux:icon name="chart-bar-square"
                                                class="w-12 h-12 text-indigo-300 dark:text-indigo-400" />
                                        </div>
                                        <h3 class="text-xl font-medium text-slate-800 dark:text-slate-200">No accounts
                                            found</h3>
                                        <p class="mt-2 text-slate-500 dark:text-slate-400 max-w-sm">Get started by
                                            creating
                                            your chart of accounts using the "New Account" button above.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $accounts->links() }}
            </div>
        </x-glass-card>
    </div>
</div>
