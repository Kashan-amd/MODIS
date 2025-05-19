<?php

use Livewire\Volt\Component;
use App\Models\Vendor;
use Livewire\WithPagination;
use Illuminate\Support\Collection;

new class extends Component {
    use WithPagination;

    public $name = '';
    public $category = '';
    public $contact_number = '';
    public $contact_person = '';
    public $address = '';
    public $editingVendorId = null;
    public $isEditing = false;
    public $searchQuery = '';

    public function rules()
    {
        return [
            'name' => 'required|min:2|max:255',
            'category' => 'nullable|max:255',
            'contact_number' => 'nullable|max:20',
            'contact_person' => 'nullable|max:255',
            'address' => 'nullable|max:255',
        ];
    }

    public function saveVendor()
    {
        $this->validate();

        if ($this->isEditing) {
            $vendor = Vendor::find($this->editingVendorId);
            $vendor->update([
                'name' => $this->name,
                'category' => $this->category,
                'contact_number' => $this->contact_number,
                'contact_person' => $this->contact_person,
                'address' => $this->address,
            ]);

            $this->dispatch('vendor-updated', 'Vendor updated successfully');
        } else {
            Vendor::create([
                'name' => $this->name,
                'category' => $this->category,
                'contact_number' => $this->contact_number,
                'contact_person' => $this->contact_person,
                'address' => $this->address,
            ]);

            $this->dispatch('vendor-created', 'Vendor created successfully');
        }

        $this->resetForm();
        $this->modal('vendor-form')->close();
    }

    public function editVendor($vendorId)
    {
        $this->isEditing = true;
        $this->editingVendorId = $vendorId;

        $vendor = Vendor::find($vendorId);
        $this->name = $vendor->name;
        $this->category = $vendor->category ?? '';
        $this->contact_number = $vendor->contact_number ?? '';
        $this->contact_person = $vendor->contact_person ?? '';
        $this->address = $vendor->address ?? '';

        $this->modal('vendor-form')->show();
    }

    public function deleteVendor($vendorId)
    {
        Vendor::destroy($vendorId);
        $this->dispatch('vendor-deleted', 'Vendor deleted successfully');
    }

    public function resetForm()
    {
        $this->reset(['name', 'category', 'contact_number', 'contact_person', 'address', 'editingVendorId', 'isEditing']);
        $this->resetValidation();
    }

    public function cancelEdit()
    {
        $this->resetForm();
        $this->modal('vendor-form')->close();
    }

    public function with(): array
    {
        $query = Vendor::query()
            ->when($this->searchQuery, function ($query, $search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc');

        return [
            'vendors' => $query->paginate(10),
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
                    <flux:icon name="building-storefront" class="h-10 w-10" />
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-slate-100">Vendors</h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Manage your vendors and their contact information</p>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Add vendor button -->
                <flux:modal.trigger name="vendor-form">
                    <flux:button variant="primary">New Vendor</flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- Vendor Form -->
        <flux:modal name="vendor-form" class="w-full max-w-2xl">
            <x-glass-card colorScheme="indigo" class="overflow-hidden">
                <form wire:submit="saveVendor">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium">Vendor Name</label>
                            <flux:input id="name" type="text" wire:model="name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter vendor name">
                            </flux:input>
                            @error('name')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-medium">Category</label>
                            <flux:input id="category" type="text" wire:model="category"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter vendor category">
                            </flux:input>
                            @error('category')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="contact_person" class="block text-sm font-medium">Contact Person</label>
                                <flux:input id="contact_person" type="text" wire:model="contact_person"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Enter contact person name">
                                </flux:input>
                                @error('contact_person')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="contact_number" class="block text-sm font-medium">Contact Number</label>
                                <flux:input id="contact_number" type="text" wire:model="contact_number"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Enter contact number">
                                </flux:input>
                                @error('contact_number')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="address" class="block text-sm font-medium">Address</label>
                            <flux:textarea id="address" wire:model="address"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter address" rows="2">
                            </flux:textarea>
                            @error('address')
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
                            {{ $isEditing ? 'Update Vendor' : 'Create Vendor' }}
                        </flux:button>
                    </div>
                </form>
            </x-glass-card>
        </flux:modal>

        <!-- Vendors List -->
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">
                    <flux:input type="text" wire:model.live.debounce.300ms="searchQuery"
                        class="block w-full rounded-lg border-0 focus:ring-2 focus:ring-indigo-500"
                        placeholder="Search vendors...">
                    </flux:input>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-indigo-300 mr-2">{{ $vendors->total() }} {{ Str::plural('vendor',
                        $vendors->total()) }}</span>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg">
                <table class="min-w-full divide-y divide-indigo-200/20">
                    <thead class="bg-gradient-to-r from-indigo-900/40 to-indigo-800/30 backdrop-blur-sm">
                        <tr>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Vendor Name</span>
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
                                    <span>Contact Person</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Contact Number</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <span>Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-indigo-900/10 backdrop-blur-sm divide-y divide-indigo-200/10">
                        @forelse ($vendors as $vendor)
                        <tr class="hover:bg-indigo-900/20 transition-colors duration-200">
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium text-indigo-100">{{ $vendor->name }}</div>
                                @if ($vendor->address)
                                <div class="text-xs text-indigo-300 mt-1 truncate max-w-xs">{{ $vendor->address }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-200">
                                {{ $vendor->category ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-200">
                                {{ $vendor->contact_person ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-200">
                                {{ $vendor->contact_number ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end items-center space-x-2">
                                    <flux:button size="xs" variant="primary" wire:click="editVendor({{ $vendor->id }})">
                                        Edit
                                    </flux:button>
                                    <flux:button size="xs" variant="danger"
                                        wire:confirm="Are you sure you want to delete this vendor?"
                                        wire:click="deleteVendor({{ $vendor->id }})">
                                        Delete
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <div class="text-indigo-300 text-lg font-medium">No vendors found</div>
                                    <p class="text-indigo-400 text-sm">
                                        Create your first vendor by clicking the "New Vendor" button above.
                                    </p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $vendors->links() }}
            </div>
        </x-glass-card>

    </div>
</div>