<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\Organization;
use App\Models\Client;
use App\Models\Vendor;
use App\Models\ClientAccount;
use App\Models\VendorAccount;
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
    public $current_balance = 0.0;
    public $opening_balance = 0.0;
    public $balance_date = '';
    public $organization_id = '';
    public $parent_id = null;
    public $level = 0;
    public $client_id = null; // For linking clients to receivable accounts
    public $vendor_id = null; // For linking vendors to payable accounts

    // UI State
    public $editingAccountId = null;
    public $isEditing = false;
    public $searchQuery = '';
    public $formMode = 'parent'; // parent, child, grandchild
    public $organizationFilter = 1; // Default to OSC organization (ID: 1)

    protected function rules(): array
    {
        return [
            'account_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('chart_of_accounts', 'account_number')->where(function ($query) {
                    return $query->where('organization_id', $this->organization_id)->when($this->isEditing, fn($q) => $q->where('id', '!=', $this->editingAccountId));
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Account::getTypes()))],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'opening_balance' => ['nullable', 'numeric'],
            'balance_date' => ['nullable', 'date'],
            'organization_id' => ['required', 'exists:organizations,id'],
            'parent_id' => [Rule::when($this->formMode !== 'parent', ['required', 'exists:chart_of_accounts,id'], ['nullable'])],
            'client_id' => [Rule::when($this->isReceivableAccount(), ['required', 'exists:clients,id'], ['nullable'])],
            'vendor_id' => [Rule::when($this->isPayableAccount(), ['required', 'exists:vendors,id'], ['nullable'])],
            'level' => ['required', 'integer', 'min:0', 'max:2'],
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
        // Always set organization_id based on the current organization filter
        $this->organization_id = $this->organizationFilter;
    }

    public function saveAccount(): void
    {
        try {
            $this->validate();
        } catch (\Exception $e) {
            $this->dispatch('account-error', 'Validation failed: ' . $e->getMessage());
            return;
        }

        try {
            $data = $this->getAccountData();

            // Debug logging
            \Log::info('Saving account', [
                'formMode' => $this->formMode,
                'data' => $data,
                'isEditing' => $this->isEditing,
            ]);

            if ($this->isEditing) {
                $account = Account::findOrFail($this->editingAccountId);
                $account->update($data);
            } else {
                switch ($this->formMode) {
                    case 'parent':
                        $account = Account::createParentAccount($data);
                        break;
                    case 'child':
                        $parent = Account::findOrFail($this->parent_id);
                        $account = $parent->createChildAccount($data);
                        break;
                    case 'grandchild':
                        $parent = Account::findOrFail($this->parent_id);
                        $account = $parent->createGrandchildAccount($data);
                        break;
                }

                // If this is a receivable account and a client is selected, link them
                if ($this->isReceivableAccount() && $this->client_id && isset($account)) {
                    $client = Client::findOrFail($this->client_id);

                    // Create relationship in the pivot table
                    $client->accounts()->attach($account->account_number, [
                        'organization_id' => $this->organization_id
                    ]);
                }

                // If this is a payable account and a vendor is selected, link them
                if ($this->isPayableAccount() && $this->vendor_id && isset($account)) {
                    $vendor = Vendor::findOrFail($this->vendor_id);

                    // Create relationship in the pivot table
                    $vendor->accounts()->attach($account->account_number, [
                        'organization_id' => $this->organization_id
                    ]);
                }
            }

            $this->dispatch($this->isEditing ? 'account-updated' : 'account-created', 'Account ' . ($this->isEditing ? 'updated' : 'created') . ' successfully!');

            $this->resetFormAndCloseModal();
        } catch (\Exception $e) {
            \Log::error('Error saving account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('account-error', 'Error saving account: ' . $e->getMessage());
        }
    }

    /**
     * Check if the current account being created is a receivable account
     */
    private function isReceivableAccount(): bool
    {
        if (!$this->parent_id) {
            return false;
        }

        try {
            $parent = Account::find($this->parent_id);
            if (!$parent) {
                return false;
            }

            // Check if the parent account is "Accounts Receivable" (account number typically starts with 12)
            // or if the account name contains "receivable"
            return str_contains(strtolower($parent->name), 'receivable') ||
                   str_starts_with($parent->account_number, '04');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all clients for the client selection dropdown
     */
    private function getClients(): Collection
    {
        return Client::orderBy('name')->get();
    }

    /**
     * Check if the current account being created is a payable account
     */
    private function isPayableAccount(): bool
    {
        if (!$this->parent_id) {
            return false;
        }

        try {
            $parent = Account::find($this->parent_id);
            if (!$parent) {
                return false;
            }

            // Check if the parent account is "Accounts Payable" (account number starts with 20)
            // or if the account name contains "payable"
            return str_contains(strtolower($parent->name), 'payable') ||
                   str_starts_with($parent->account_number, '20');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all vendors for the vendor selection dropdown
     */
    private function getVendors(): Collection
    {
        return Vendor::orderBy('name')->get();
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
            'organization_id' => $this->organization_id, // Always use the set organization_id
            'level' => $this->level,
        ];
    }

    public function editAccount(int $accountId): void
    {
        try {
            $account = Account::findOrFail($accountId);
            $this->isEditing = true;
            $this->editingAccountId = $accountId;

            // Determine form mode based on level
            $this->formMode = match ($account->level) {
                Account::LEVEL_PARENT => 'parent',
                Account::LEVEL_CHILD => 'child',
                Account::LEVEL_GRANDCHILD => 'grandchild',
                default => 'parent',
            };

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

            if ($account->transactionEntries()->exists()) {
                throw new \RuntimeException('Cannot delete account with existing transactions.');
            }

            if ($account->hasChildren()) {
                throw new \RuntimeException('Cannot delete account with existing sub-accounts.');
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
            'level' => $account->level,
        ]);
    }

    /**
     * Handle client selection - auto-populate account name
     */
    public function updatedClientId($value): void
    {
        if ($value && $this->isReceivableAccount()) {
            try {
                $client = Client::findOrFail($value);
            } catch (\Exception $e) {
                // Ignore error, user can manually enter name
                $this->dispatch('account-error', 'Error loading client: ' . $e->getMessage());
            }
        }
    }

    public function updatedParentId($value): void
    {
        if ($this->formMode !== 'parent' && $value) {
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
        $this->reset(['account_number', 'name', 'type', 'description', 'is_active', 'current_balance', 'opening_balance', 'editingAccountId', 'isEditing', 'parent_id', 'level', 'client_id', 'vendor_id']);

        $this->balance_date = now()->format('Y-m-d');
        $this->organization_id = $this->organizationFilter; // Always use current filter
        $this->resetValidation();
    }

    public function prepareParentAccountForm(): void
    {
        $this->resetFormAndOpenModal('parent', Account::LEVEL_PARENT);
    }

    public function prepareChildAccountForm(): void
    {
        $this->resetFormAndOpenModal('child', Account::LEVEL_CHILD);
    }

    public function prepareGrandchildAccountForm(): void
    {
        $this->resetFormAndOpenModal('grandchild', Account::LEVEL_GRANDCHILD);
    }

    private function resetFormAndOpenModal(string $mode, int $level): void
    {
        $this->resetForm();
        $this->formMode = $mode;
        $this->level = $level;
        $this->parent_id = null;

        // Always use the current organization filter for new accounts
        $this->organization_id = $this->organizationFilter;

        $this->modal('account-form')->show();
    }

    private function getAccountsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // We'll handle the hierarchical ordering in the with() method instead
        $query = Account::query()->with(['parent', 'children', 'organization']);

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->searchQuery}%")
                    ->orWhere('account_number', 'like', "%{$this->searchQuery}%")
                    // ->orWhere('type', 'like', "%{$this->searchQuery}%")
                    ->orWhere('description', 'like', "%{$this->searchQuery}%");
            });
        }

        // Filter by organization if set
        if ($this->organizationFilter) {
            $query->where(function($q) {
                $q->where('organization_id', $this->organizationFilter)
                  // Also include parent accounts (level 0) that might be associated with any organization
                  ->orWhere(function($subQuery) {
                      $subQuery->where('level', 0)
                               ->whereNull('organization_id');
                  });
            });
        }

        // Simple ordering by account number for now
        return $query->orderBy('account_number');
    }

    private function getHierarchicalAccounts()
    {
        $allAccounts = $this->getAccountsQuery()->get();

        if ($this->searchQuery) {
            // If searching, return flat results
            return $allAccounts;
        }

        $hierarchical = collect();

        // Get all parent accounts first
        $parents = $allAccounts->where('level', 0)->sortBy('account_number');

        foreach ($parents as $parent) {
            $hierarchical->push($parent);

            // Get children of this parent
            $children = $allAccounts->where('parent_id', $parent->id)->sortBy('account_number');

            foreach ($children as $child) {
                $hierarchical->push($child);

                // Get grandchildren of this child
                $grandchildren = $allAccounts->where('parent_id', $child->id)->sortBy('account_number');

                foreach ($grandchildren as $grandchild) {
                    $hierarchical->push($grandchild);
                }
            }
        }

        return $hierarchical;
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
        if ($this->formMode === 'parent') {
            return collect();
        }

        return Account::getValidParents($this->organization_id, $this->level);
    }

    public function with(): array
    {
        return [
            'accounts' => $this->getAccountsQuery()->paginate(100),
            'organizations' => $this->getOrganizations(),
            'accountTypes' => $this->getAccountTypes(),
            'validParents' => $this->getValidParents(),
            'clients' => $this->getClients(),
            'vendors' => $this->getVendors(),
        ];
    }

    public function updatedOrganizationFilter(): void
    {
        // Reset pagination when changing organization filter
        $this->resetPage();

        // Update organization_id for account creation to match the filter
        // Only update if not currently editing an existing account
        if (!$this->isEditing) {
            $this->organization_id = $this->organizationFilter;
        }
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
                <!-- Add parent account button -->
                <flux:modal.trigger name="account-form" wire:click="prepareParentAccountForm">
                    <flux:button variant="primary" class="flex items-center">
                        <flux:icon name="check-badge" class="h-4 w-4 mr-2" />
                        <span>New Head Account</span>
                    </flux:button>
                </flux:modal.trigger>

                <!-- Add child account button -->
                <flux:modal.trigger name="account-form" wire:click="prepareChildAccountForm">
                    <flux:button variant="primary" class="flex items-center">
                        <flux:icon name="arrow-right-circle" class="h-4 w-4 mr-2" />
                        <span>New Sub Account</span>
                    </flux:button>
                </flux:modal.trigger>

                <!-- Add grandchild account button -->
                <flux:modal.trigger name="account-form" wire:click="prepareGrandchildAccountForm">
                    <flux:button variant="primary" class="flex items-center">
                        <flux:icon name="arrow-turn-down-right" class="h-4 w-4 mr-2" />
                        <span>New Sub Item</span>
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
                                Edit
                                {{ ucfirst($formMode) === 'Parent' ? 'Head' : (ucfirst($formMode) === 'Child' ? 'Sub' :
                                'Sub Item') }}
                                Account
                                @else
                                New
                                {{ ucfirst($formMode) === 'Parent' ? 'Head' : (ucfirst($formMode) === 'Child' ? 'Sub' :
                                'Sub Item') }}
                                Account
                                @endif
                                <span class="text-sm text-indigo-300 ml-2">(Level {{ $level }})</span>
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                @if ($formMode !== 'parent')
                                <!-- Parent Selection for Child and Grandchild -->
                                <div>
                                    <label for="parent_id" class="block text-sm font-medium text-indigo-100">
                                        {{ $formMode === 'child' ? 'Parent Account' : 'Child Account' }}
                                        <span class="text-red-400">*</span>
                                    </label>
                                    <flux:select id="parent_id" wire:model.live="parent_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <flux:select.option value="">
                                            Select {{ $formMode === 'child' ? 'Parent' : 'Child' }} Account
                                        </flux:select.option>
                                        @foreach ($this->getValidParents() as $parentAccount)
                                        <flux:select.option value="{{ $parentAccount->id }}">
                                            {{ $parentAccount->account_number }} - {{ $parentAccount->name }}
                                        </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @error('parent_id')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror

                                    @if ($parent_id && $account_number)
                                    <div class="mt-2 text-xs text-indigo-300">
                                        Auto-generated: <span class="font-semibold">{{ $account_number }}</span>
                                    </div>
                                    @endif
                                </div>
                                @endif

                                @if ($formMode === 'parent')
                                <!-- Account Number for Parent Accounts -->
                                <div>
                                    <label for="account_number" class="block text-sm font-medium text-indigo-100">
                                        Account Number <span class="text-red-400">*</span>
                                        <span class="text-xs text-gray-500">(e.g., 10, 11, 12...)</span>
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

                                <!-- Organization Display -->
                                <div>
                                    <label class="block text-sm font-medium text-indigo-100">
                                        Organization
                                    </label>
                                    <div
                                        class="mt-1 block w-full px-3 py-2 bg-indigo-900/20 border border-indigo-200/20 rounded-md text-indigo-100">
                                        @php
                                        $currentOrg = $organizations->find($this->organizationFilter);
                                        @endphp
                                        {{ $currentOrg ? $currentOrg->name : 'No Organization Selected' }}
                                    </div>
                                    <div class="mt-1 text-xs text-indigo-300">
                                        Automatically set from the organization filter above
                                    </div>
                                </div>

                                <!-- Client Selection for Receivable Accounts -->
                                @if ($this->isReceivableAccount())
                                <div>
                                    <label for="client_id" class="block text-sm font-medium text-indigo-100">
                                        Client <span class="text-red-400">*</span>
                                    </label>
                                    <flux:select id="client_id" wire:model="client_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <flux:select.option value="">Select Client</flux:select.option>
                                        @foreach ($clients as $client)
                                        <flux:select.option value="{{ $client->id }}">
                                            {{ $client->name }}{{ $client->business_name ? ' (' . $client->business_name
                                            . ')' : '' }}
                                        </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @error('client_id')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                    <div class="mt-1 text-xs text-indigo-300">
                                        This client will be linked to the receivable account
                                    </div>
                                </div>
                                @endif

                                <!-- Vendor Selection for Payable Accounts -->
                                @if ($this->isPayableAccount())
                                <div>
                                    <label for="vendor_id" class="block text-sm font-medium text-indigo-100">
                                        Vendor <span class="text-red-400">*</span>
                                    </label>
                                    <flux:select id="vendor_id" wire:model="vendor_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <flux:select.option value="">Select Vendor</flux:select.option>
                                        @foreach ($vendors as $vendor)
                                        <flux:select.option value="{{ $vendor->id }}">
                                            {{ $vendor->name }}{{ $vendor->business_name ? ' (' . $vendor->business_name
                                            . ')' : '' }}
                                        </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @error('vendor_id')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                    <div class="mt-1 text-xs text-indigo-300">
                                        This vendor will be linked to the payable account
                                    </div>
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
                <div class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-4 mb-4 md:mb-0">
                    <div class="w-full md:w-64">
                        <flux:input type="text" wire:model.live.debounce.300ms="searchQuery"
                            class="block w-full rounded-lg border-0 focus:ring-2 focus:ring-indigo-500"
                            placeholder="Search accounts...">
                        </flux:input>
                    </div>
                    <div class="w-full md:w-64">
                        <flux:select wire:model.live="organizationFilter"
                            class="block w-full rounded-lg border-0 focus:ring-2 focus:ring-indigo-500">
                            <flux:select.option value="all">All Organizations</flux:select.option>
                            @foreach ($organizations as $organization)
                            <flux:select.option value="{{ $organization->id }}">{{ $organization->name }}
                                @if($organization->id == 1) (Default) @endif
                            </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
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
                                <div class="font-medium text-indigo-100 flex items-center">
                                    @if ($account->level === 0)
                                    <flux:icon name="check-badge" class="h-4 w-4 mr-2 text-indigo-300" />
                                    @elseif ($account->level === 1)
                                    <span class="inline-block w-6"></span>
                                    <flux:icon name="arrow-right-circle" class="h-4 w-4 mr-2 text-blue-300" />
                                    @else
                                    <span class="inline-block w-12"></span>
                                    <flux:icon name="arrow-turn-down-right" class="h-4 w-4 mr-2 text-green-300" />
                                    @endif

                                    {{ $account->account_number }}

                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-200">
                                <div style="margin-left: {{ $account->level * 24 }}px">
                                    {{ $account->name }}
                                    {{-- @if ($account->is_parent)
                                    <span class="ml-2 text-xs text-indigo-400">(Parent)</span>
                                    @endif --}}
                                </div>
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

                                    {{-- <flux:button size="xs" variant="danger"
                                        wire:confirm="Are you sure you want to delete this account? This action cannot be undone if the account has transactions."
                                        wire:click="deleteAccount({{ $account->id }})">
                                        Delete
                                    </flux:button> --}}
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
                                        <flux:icon name="chart-bar-square"
                                            class="w-12 h-12 text-indigo-300 dark:text-indigo-400" />
                                    </div>
                                    <h3 class="text-xl font-medium text-slate-800 dark:text-slate-200">No accounts
                                        found</h3>
                                    <p class="mt-2 text-slate-500 dark:text-slate-400 max-w-sm">
                                        @if($organizationFilter || $searchQuery)
                                        No accounts match your current filters. Try adjusting your search or
                                        organization filter.
                                        @else
                                        Get started by creating your chart of accounts using the "New Account" button
                                        above.
                                        @endif
                                    </p>
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