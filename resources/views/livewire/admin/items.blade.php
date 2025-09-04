<?php

use Livewire\Volt\Component;
use App\Models\Item;
use App\Models\Account;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $name = "";
    public $category = "";
    public $description = "";
    public $cogs_account_id = "";
    public $editingItemId = null;
    public $isEditing = false;
    public $searchQuery = "";

    public function rules()
    {
        return [
            "name" => "required|min:2|max:255",
            "category" => "nullable|max:255",
            "description" => "nullable|max:255",
            "cogs_account_id" => "required|exists:chart_of_accounts,id",
        ];
    }

    public function saveItem()
    {
        $this->validate();

        if ($this->isEditing) {
            $item = Item::find($this->editingItemId);
            $item->update([
                "name" => $this->name,
                "category" => $this->category,
                "description" => $this->description,
                "cogs_account_id" => $this->cogs_account_id,
            ]);

            $this->dispatch("item-updated", "Item updated successfully");
        } else {
            Item::create([
                "name" => $this->name,
                "category" => $this->category,
                "description" => $this->description,
                "cogs_account_id" => $this->cogs_account_id,
            ]);

            $this->dispatch("item-created", "Item created successfully");
        }

        $this->resetForm();
        $this->modal("item-form")->close();
    }

    public function editItem($itemId)
    {
        $this->isEditing = true;
        $this->editingItemId = $itemId;

        $item = Item::find($itemId);
        $this->name = $item->name;
        $this->category = $item->category ?? "";
        $this->description = $item->description ?? "";
        $this->cogs_account_id = $item->cogs_account_id ?? "";

        $this->modal("item-form")->show();
    }

    public function deleteItem($itemId)
    {
        Item::destroy($itemId);
        $this->dispatch("item-deleted", "Item deleted successfully");
    }

    public function resetForm()
    {
        $this->reset(["name", "category", "description", "cogs_account_id", "editingItemId", "isEditing"]);
        $this->resetValidation();
    }

    public function cancelEdit()
    {
        $this->resetForm();
        $this->modal("item-form")->close();
    }

    public function getCogsAccount()
    {
        // Get COGS parent account and its children
        $cogsAccount = Account::where("name", "Cost of Goods Sold")->first();
        $cogsChildAccounts = collect();

        if ($cogsAccount) {
            $cogsChildAccounts = Account::where("parent_id", $cogsAccount->id)->where("is_active", true)->orderBy("name")->get();
        }

        return $cogsChildAccounts;
    }

    public function getItemsWithCogs()
    {
        // Get all items with their COGS accounts, filtered by search
        $allItems = Item::query()
            ->with("cogsAccount")
            ->when($this->searchQuery, function ($query, $search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery
                        ->where("name", "like", "%{$search}%")
                        ->orWhere("category", "like", "%{$search}%")
                        ->orWhere("description", "like", "%{$search}%");
                });
            })
            ->orderBy("name")
            ->get();

        return $allItems;
    }

    public function groupAndCategorizeItems($allItems, $cogsChildAccounts)
    {
        // Group items by their COGS accounts and prepare categorized data

        // Group items by COGS account
        $groupedItems = $allItems->groupBy("cogs_account_id");

        // Create structured data with accounts and their items
        $categorizedData = $cogsChildAccounts->map(
            fn($account) => [
                "account" => $account,
                "items" => $groupedItems->get($account->id, collect()),
                "items_count" => $groupedItems->get($account->id, collect())->count(),
            ],
        );

        // Add items without COGS accounts
        $itemsWithoutAccount = $groupedItems->get(null, collect())->merge($groupedItems->get("", collect()));
        if ($itemsWithoutAccount->isNotEmpty()) {
            $categorizedData->push([
                "account" => null,
                "items" => $itemsWithoutAccount,
                "items_count" => $itemsWithoutAccount->count(),
            ]);
        }

        return $categorizedData;
    }

    public function with(): array
    {
        $cogsChildAccounts = $this->getCogsAccount();
        $allItems = $this->getItemsWithCogs();
        $categorizedData = $this->groupAndCategorizeItems($allItems, $cogsChildAccounts);

        return [
            "categorizedData" => $categorizedData,
            "cogsChildAccounts" => $cogsChildAccounts,
            "totalItems" => $allItems->count(),
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
                    <flux:icon name="cube" class="h-10 w-10" />
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-slate-100">Items</h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Manage your inventory items and their information</p>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Add item button -->
                <flux:modal.trigger name="item-form">
                    <flux:button variant="primary">New Item</flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- Item Form -->
        <flux:modal name="item-form" class="w-full max-w-2xl">
            <x-glass-card colorScheme="indigo" class="overflow-hidden">
                <form wire:submit="saveItem">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium">Item Name</label>
                            <flux:input id="name" type="text" wire:model="name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter item name">
                            </flux:input>
                            @error('name')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="cogs_account_id" class="block text-sm font-medium">COGS Account</label>
                            <flux:select id="cogs_account_id" wire:model="cogs_account_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select COGS Account</option>
                                @foreach($cogsChildAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->name }}
                                </option>
                                @endforeach
                            </flux:select>
                            @error('cogs_account_id')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-medium">Category</label>
                            <flux:input id="category" type="text" wire:model="category"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter item category">
                            </flux:input>
                            @error('category')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium">Description</label>
                            <flux:textarea id="description" wire:model="description"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter item description" rows="3">
                            </flux:textarea>
                            @error('description')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        @if ($isEditing)
                        <flux:button type="button" variant="danger" wire:click="cancelEdit"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 mr-2">
                            Cancel
                        </flux:button>
                        @endif
                        <flux:button type="submit" variant="primary"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ $isEditing ? 'Update Item' : 'Create Item' }}
                        </flux:button>
                    </div>
                </form>
            </x-glass-card>
        </flux:modal>

        <!-- Items List -->
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">
                    <flux:input type="text" wire:model.live.debounce.300ms="searchQuery"
                        class="block w-full rounded-lg border-0 focus:ring-2 focus:ring-indigo-500"
                        placeholder="Search items...">
                    </flux:input>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-indigo-300 mr-2">{{ $totalItems }} {{ Str::plural('item', $totalItems)
                        }}</span>
                </div>
            </div>

            @if($categorizedData->isEmpty())
            <div class="text-center py-12">
                <div class="flex flex-col items-center justify-center space-y-2">
                    <div class="text-indigo-300 text-lg font-medium">No items found</div>
                    <p class="text-indigo-400 text-sm">
                        Create your first item by clicking the "New Item" button above.
                    </p>
                </div>
            </div>
            @else
            <div class="space-y-6">
                @foreach($categorizedData as $categoryData)
                @if($categoryData['items_count'] > 0)
                <!-- Category Header -->
                <div class="border-t border-indigo-200/20 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div
                                    class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                                    <flux:icon name="cube-transparent" class="w-5 h-5 text-white" />
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-indigo-100">
                                    @if($categoryData['account'])
                                    {{ $categoryData['account']->account_number }} - {{ $categoryData['account']->name
                                    }}
                                    @else
                                    Unassigned Items
                                    @endif
                                </h3>
                                <p class="text-sm text-indigo-300">
                                    {{ $categoryData['items_count'] }} {{ Str::plural('item',
                                    $categoryData['items_count']) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items in this category -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 ml-4">
                    @foreach($categoryData['items'] as $item)
                    <div
                        class="bg-indigo-900/20 backdrop-blur-sm rounded-lg border border-indigo-200/10 p-4 hover:bg-indigo-900/30 transition-colors duration-200">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-medium text-indigo-100 truncate">
                                    {{ $item->name }}
                                </h4>
                                @if($item->category)
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-900/50 text-indigo-200 mt-1">
                                    {{ $item->category }}
                                </span>
                                @endif
                                @if($item->description)
                                <p class="text-xs text-indigo-300 mt-2 line-clamp-2">
                                    {{ $item->description }}
                                </p>
                                @endif
                            </div>
                            <div class="flex items-center space-x-1 ml-2">
                                <flux:button size="xs" variant="primary" wire:click="editItem({{ $item->id }})"
                                    class="flex-shrink-0">
                                    <flux:icon name="pencil" class="w-3 h-3" />
                                </flux:button>
                                <flux:button size="xs" variant="danger"
                                    wire:confirm="Are you sure you want to delete this item?"
                                    wire:click="deleteItem({{ $item->id }})" class="flex-shrink-0">
                                    <flux:icon name="trash" class="w-3 h-3" />
                                </flux:button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                @endforeach
            </div>
            @endif
        </x-glass-card>

    </div>
</div>
