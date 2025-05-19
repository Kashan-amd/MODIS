<?php

use Livewire\Volt\Component;
use App\Models\Item;
use Livewire\WithPagination;
use Illuminate\Support\Collection;

new class extends Component {
    use WithPagination;

    public $name = '';
    public $category = '';
    public $description = '';
    public $editingItemId = null;
    public $isEditing = false;
    public $searchQuery = '';

    public function rules()
    {
        return [
            'name' => 'required|min:2|max:255',
            'category' => 'nullable|max:255',
            'description' => 'nullable|max:255',
        ];
    }

    public function saveItem()
    {
        $this->validate();

        if ($this->isEditing) {
            $item = Item::find($this->editingItemId);
            $item->update([
                'name' => $this->name,
                'category' => $this->category,
                'description' => $this->description,
            ]);

            $this->dispatch('item-updated', 'Item updated successfully');
        } else {
            Item::create([
                'name' => $this->name,
                'category' => $this->category,
                'description' => $this->description,
            ]);

            $this->dispatch('item-created', 'Item created successfully');
        }

        $this->resetForm();
        $this->modal('item-form')->close();
    }

    public function editItem($itemId)
    {
        $this->isEditing = true;
        $this->editingItemId = $itemId;

        $item = Item::find($itemId);
        $this->name = $item->name;
        $this->category = $item->category ?? '';
        $this->description = $item->description ?? '';

        $this->modal('item-form')->show();
    }

    public function deleteItem($itemId)
    {
        Item::destroy($itemId);
        $this->dispatch('item-deleted', 'Item deleted successfully');
    }

    public function resetForm()
    {
        $this->reset(['name', 'category', 'description', 'editingItemId', 'isEditing']);
        $this->resetValidation();
    }

    public function cancelEdit()
    {
        $this->resetForm();
        $this->modal('item-form')->close();
    }

    public function with(): array
    {
        $query = Item::query()
            ->when($this->searchQuery, function ($query, $search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc');

        return [
            'items' => $query->paginate(10),
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
                    <span class="text-sm text-indigo-300 mr-2">{{ $items->total() }} {{ Str::plural('item',
                        $items->total()) }}</span>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg">
                <table class="min-w-full divide-y divide-indigo-200/20">
                    <thead class="bg-gradient-to-r from-indigo-900/40 to-indigo-800/30 backdrop-blur-sm">
                        <tr>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Item Name</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Category</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Description</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <span>Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-indigo-900/10 backdrop-blur-sm divide-y divide-indigo-200/10">
                        @forelse ($items as $item)
                        <tr class="hover:bg-indigo-900/20 transition-colors duration-200">
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium text-indigo-100">{{ $item->name }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-200">
                                {{ $item->category ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-200">
                                <div class="truncate max-w-xs">{{ $item->description ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end items-center space-x-2">
                                    <flux:button size="xs" variant="primary" wire:click="editItem({{ $item->id }})">
                                        Edit
                                    </flux:button>
                                    <flux:button size="xs" variant="danger"
                                        wire:confirm="Are you sure you want to delete this item?"
                                        wire:click="deleteItem({{ $item->id }})">
                                        Delete
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <div class="text-indigo-300 text-lg font-medium">No items found</div>
                                    <p class="text-indigo-400 text-sm">
                                        Create your first item by clicking the "New Item" button above.
                                    </p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $items->links() }}
            </div>
        </x-glass-card>

    </div>
</div>