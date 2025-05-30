<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\Organization;
use Livewire\WithPagination;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

new class extends Component {
    use WithPagination;

    // Form Properties
    public $account_number = '';
    public $name = '';
    public $type = '';
    public $description = '';
    public $is_active = true;
    public $current_balance = 0.00;
    public $opening_balance = 0.00;
    public $balance_date = '';
    public $organization_id = '';
    public $parent_id = null;
    public $is_parent = false;

    // UI State
    public $editingAccountId = null;
    public $isEditing = false;
    public $searchQuery = '';
    public $formMode = 'head';

    protected function rules(): array
    {
        return [
            'account_number' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($this->formMode === 'head' && str_contains($value, '-')) {
                        $fail('Head accounts should not contain a hyphen (-).');
                    }
                },
                Rule::unique('chart_of_accounts', 'account_number')->where(function ($query) {
                    return $query->where('organization_id', $this->organization_id)
                                ->when($this->isEditing, fn($q) => $q->where('id', '!=', $this->editingAccountId));
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Account::getTypes()))],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'opening_balance' => ['nullable', 'numeric'],
            'balance_date' => ['nullable', 'date'],
            'organization_id' => [
                Rule::when($this->formMode === 'sub' || !$this->is_parent,
                    ['required', 'exists:organizations,id'],
                    ['nullable', 'exists:organizations,id']
                )
            ],
            'parent_id' => [
                Rule::when($this->formMode === 'sub',
                    ['required', 'exists:chart_of_accounts,id'],
                    ['nullable', 'exists:chart_of_accounts,id']
                ),
                function ($attribute, $value, $fail) {
                    if ($this->isEditing && $value === $this->editingAccountId) {
                        $fail('An account cannot be its own parent.');
                    }
                },
            ],
            'is_parent' => [
                'boolean',
                function ($attribute, $value, $fail) {
                    if ($this->formMode === 'sub' && $value) {
                        $fail('Sub-accounts cannot be parent accounts.');
                    }
                },
            ],
        ];
    }

    public function mount(): void
    {
        $this->initializeDefaults();
    }

    private function initializeDefaults(): void
    {
        $this->balance_date = now()->format('Y-m-d');
        $this->loadDefaultOrganization();
    }

    private function loadDefaultOrganization(): void
    {
        if ($organization = Organization::first()) {
            $this->organization_id = $organization->id;
        }
    }

    public function saveAccount(): void
    {
        $this->validate();

        try {
            if ($this->formMode === 'sub') {
                $this->saveSubAccount();
            } else {
                $this->saveHeadAccount();
            }

            $this->dispatch(
                $this->isEditing ? 'account-updated' : 'account-created',
                'Account ' . ($this->isEditing ? 'updated' : 'created') . ' successfully!'
            );

            $this->resetFormAndCloseModal();
        } catch (\Exception $e) {
            $this->dispatch('account-error', 'Error saving account: ' . $e->getMessage());
        }
    }

    private function saveSubAccount(): void
    {
        $data = $this->getAccountData();

        if ($this->isEditing) {
            $account = Account::findOrFail($this->editingAccountId);
            $account->update($data);
        } else {
            $parentAccount = Account::findOrFail($this->parent_id);
            $parentAccount->createSubAccount($data);
        }
    }

    private function saveHeadAccount(): void
    {
        $data = $this->getAccountData();

        if ($this->isEditing) {
            $account = Account::findOrFail($this->editingAccountId);
            $account->update($data);
        } else {
            Account::createHeadAccount($data);
        }
    }

    private function getAccountData(): array
    {
        return [
            'account_number' => $this->account_number,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'current_balance' => $this->opening_balance ?: 0,
            'opening_balance' => $this->opening_balance ?: 0,
            'balance_date' => $this->balance_date,
            'organization_id' => $this->organization_id,
            'is_parent' => $this->is_parent,
        ];
    }

    public function editAccount(int $accountId): void
    {
        try {
            $account = Account::findOrFail($accountId);
            $this->isEditing = true;
            $this->editingAccountId = $accountId;
            $this->formMode = $account->parent_id ? 'sub' : 'head';

            $this->loadAccountData($account);
            $this->modal('account-form')->show();
        } catch (\Exception $e) {
            $this->dispatch('account-error', 'Failed to load account: ' . $e->getMessage());
        }
    }

    public function deleteAccount(int $accountId): void
    {
        try {
            $account = Account::findOrFail($accountId);

            if ($account->transactions()->exists()) {
                throw new \RuntimeException('Cannot delete account with existing transactions.');
            }

            if ($account->is_parent && $account->children()->exists()) {
                throw new \RuntimeException('Cannot delete a parent account with existing sub-accounts.');
            }

            $account->delete();
            $this->dispatch('account-deleted', 'Account deleted successfully!');
        } catch (\Exception $e) {
            $this->dispatch('account-error', $e->getMessage());
        }
    }

    private function loadAccountData(Account $account): void
    {
        $this->fill([
            'account_number' => $account->account_number,
            'name' => $account->name,
            'type' => $account->type,
            'description' => $account->description,
            'is_active' => $account->is_active,
            'current_balance' => $account->current_balance,
            'opening_balance' => $account->opening_balance,
            'balance_date' => $account->balance_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'organization_id' => $account->organization_id,
            'parent_id' => $account->parent_id,
            'is_parent' => $account->is_parent,
        ]);
    }

    public function updatedParentId($value): void
    {
        if ($this->formMode === 'sub' && $value) {
            try {
                $parent = Account::findOrFail($value);
                $this->account_number = $parent->getNextSubAccountNumber();
            } catch (\Exception $e) {
                $this->dispatch('account-error', 'Error generating account number: ' . $e->getMessage());
            }
        }
    }

    public function cancelEdit(): void
    {
        $this->resetFormAndCloseModal();
    }

    private function resetFormAndCloseModal(): void
    {
        $this->resetForm();
        $this->modal('account-form')->close();
    }

    public function resetForm(): void
    {
        $this->reset([
            'account_number', 'name', 'type', 'description', 'is_active',
            'current_balance', 'opening_balance', 'editingAccountId', 'isEditing', 'parent_id', 'is_parent'
        ]);

        $this->balance_date = now()->format('Y-m-d');
        $this->resetValidation();
    }

    public function prepareHeadAccountForm(): void
    {
        $this->resetFormAndOpenModal('head', true);
    }

    public function prepareSubAccountForm(): void
    {
        $this->resetFormAndOpenModal('sub', false);
    }

    private function resetFormAndOpenModal(string $mode, bool $isParent): void
    {
        $this->resetForm();
        $this->formMode = $mode;
        $this->is_parent = $isParent;
        $this->parent_id = null;
        $this->modal('account-form')->show();
    }

    private function getAccountsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Account::query()
            ->with(['parent', 'children'])
            ->select(['*'])
            ->selectRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END as level');

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->searchQuery}%")
                  ->orWhere('account_number', 'like', "%{$this->searchQuery}%")
                  ->orWhere('description', 'like', "%{$this->searchQuery}%");
            });
        }

        return $query->orderBy('account_number');
    }

    private function getOrganizations(): Collection
    {
        return Organization::all();
    }

    private function getAccountTypes(): array
    {
        return Account::getTypes();
    }

    private function getValidParents(): Collection
    {
        return Account::getValidParents($this->organization_id);
    }

    public function with(): array
    {
        return [
            'accounts' => $this->getAccountsQuery()->paginate(18),
            'organizations' => $this->getOrganizations(),
            'accountTypes' => $this->getAccountTypes(),
            'validParents' => $this->getValidParents(),
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
                    <flux:icon name="chart-bar-square" class="h-10 w-10" />
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-slate-100">Chart of Accounts
                    </h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Manage your organization's financial accounts</p>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Add head account button -->
                <flux:modal.trigger name="account-form" wire:click="prepareHeadAccountForm">
                    <flux:button variant="primary" class="flex items-center">
                        <flux:icon name="plus" class="h-4 w-4 mr-2" />
                        <span>New Head Account</span>
                    </flux:button>
                </flux:modal.trigger>

                <!-- Add sub-account button -->
                <flux:modal.trigger name="account-form" wire:click="prepareSubAccountForm">
                    <flux:button variant="primary" class="flex items-center">
                        <flux:icon name="arrow-turn-down-right" class="h-4 w-4 mr-2" />
                        <span>New Sub-Account</span>
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- Account Form Modal -->
        <flux:modal name="account-form" class="w-full max-w-4xl">
            <x-glass-card colorScheme="indigo" class="overflow-hidden">
                <form wire:submit.prevent="saveAccount">
                    <div class="space-y-6">
                        <div class="border-b border-indigo-200/20 pb-6">
                            <h3 class="text-lg font-medium text-indigo-100">
                                @if ($isEditing)
                                Edit {{ $formMode === 'head' ? 'Head' : 'Sub' }} Account
                                @else
                                Create New {{ $formMode === 'head' ? 'Head' : 'Sub' }} Account
                                @endif
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                @if ($formMode === 'sub')
                                <!-- For sub-accounts, show parent selection first -->
                                <div>
                                    <label for="parent_id" class="block text-sm font-medium text-indigo-100">Parent
                                        Account <span class="text-red-400">*</span></label>
                                    <flux:select id="parent_id" wire:model.live="parent_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <flux:select.option value="">Select Parent Account
                                        </flux:select.option>
                                        @foreach (Account::where('is_parent', true)->where(function ($query) {
                                        $query->where('organization_id',
                                        $this->organization_id)->orWhereNull('organization_id'); // Include head parents
                                        })->orderBy('account_number')->get() as $parentAccount)
                                        <flux:select.option value="{{ $parentAccount->id }}">
                                            {{ $parentAccount->full_account_name }}
                                        </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @error('parent_id')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror

                                    @if ($parent_id && $account_number)
                                    <div class="mt-2 text-xs text-indigo-300">
                                        Auto-generated account number: <span class="font-semibold">{{ $account_number
                                            }}</span>
                                    </div>
                                    @endif
                                </div>

                                <div>
                                    <label for="organization_id" class="block text-sm font-medium">
                                        Organization <span class="text-red-400">*</span>
                                    </label>
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
                                @else
                                <!-- For head accounts, show organization and account number -->
                                <div>
                                    <label for="organization_id" class="block text-sm font-medium">
                                        Organization
                                        @if (!$is_parent)
                                        <span class="text-red-400">*</span>
                                        @else
                                        <span class="text-xs text-gray-500">(Optional for parent heads)</span>
                                        @endif
                                    </label>
                                    <flux:select id="organization_id" wire:model="organization_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @if ($is_parent)
                                        <flux:select.option value="">No Organization (Global)
                                        </flux:select.option>
                                        @else
                                        <flux:select.option value="">Select Organization
                                        </flux:select.option>
                                        @endif
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
                                        <span class="text-xs text-gray-500">(e.g., 1000, 2000)</span>
                                    </label>
                                    <flux:input id="account_number" type="text" wire:model="account_number"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Enter main account number">
                                    </flux:input>
                                    @error('account_number')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                                @endif
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
                                    <flux:checkbox id="is_active" wire:model="is_active" class="mr-2">
                                    </flux:checkbox>
                                    <label for="is_active" class="text-sm font-medium">Active Account</label>
                                </div>
                                @if ($formMode === 'head')
                                <div class="flex items-center">
                                    <flux:checkbox id="is_parent" wire:model="is_parent" class="mr-2">
                                    </flux:checkbox>
                                    <label for="is_parent" class="text-sm font-medium">Parent Account</label>
                                </div>
                                @endif
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
                                    <span>Opening Balance</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Current Balance</span>
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
                                    <span class="inline-block" style="margin-left: {{ $account->level * 20 }}px">
                                        <flux:icon name="arrow-turn-down-right" class="h-4 w-4 mr-1 text-indigo-400" />
                                    </span>
                                    @else
                                    <span class="inline-block">
                                        <flux:icon name="check-badge" class="h-3 w-4 mr-1" />
                                    </span>
                                    @endif
                                    {{ $account->account_number }}
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
                                <span class="px-3 py-1 text-xs leading-5 font-semibold rounded-full
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
                                <div class="font-medium">{{ $account->formatted_opening_balance }}</div>
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