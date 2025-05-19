<?php

use Livewire\Volt\Component;
use App\Models\Organization;
use Livewire\WithPagination;
use Illuminate\Support\Collection;

new class extends Component {
    use WithPagination;

    public $name = '';
    public $description = '';
    public $editingOrganizationId = null;
    public $isEditing = false;
    public $searchQuery = '';

    public function rules()
    {
        return [
            'name' => 'required|min:2|max:255',
            'description' => 'nullable|max:255',
        ];
    }

    public function saveOrganization()
    {
        $this->validate();

        if ($this->isEditing) {
            $organization = Organization::find($this->editingOrganizationId);
            $organization->update([
                'name' => $this->name,
                'description' => $this->description,
            ]);

            $this->dispatch('organization-updated', 'Organization updated successfully');
        } else {
            Organization::create([
                'name' => $this->name,
                'description' => $this->description,
            ]);

            $this->dispatch('organization-created', 'Organization created successfully');
        }

        $this->resetForm();
        $this->modal('organization-form')->close();
    }

    public function editOrganization($organizationId)
    {
        $this->isEditing = true;
        $this->editingOrganizationId = $organizationId;

        $organization = Organization::find($organizationId);
        $this->name = $organization->name;
        $this->description = $organization->description ?? '';
        $this->modal('organization-form')->show();
    }

    public function deleteOrganization($organizationId)
    {
        Organization::destroy($organizationId);
        $this->dispatch('organization-deleted', 'Organization deleted successfully');
    }

    public function resetForm()
    {
        $this->reset(['name', 'description', 'editingOrganizationId', 'isEditing']);
        $this->resetValidation();
    }

    public function cancelEdit()
    {
        $this->resetForm();
        $this->modal('organization-form')->close();
    }

    public function with(): array
    {
        $query = Organization::query()
            ->when($this->searchQuery, function ($query, $search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc');

        return [
            'organizations' => $query->paginate(10),
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
                    <flux:icon name="building-office" class="h-10 w-10" />
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-slate-100">Organizations
                    </h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Manage your organizations and their details</p>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Add organization button -->
                <flux:modal.trigger name="organization-form">
                    <flux:button variant="primary">New Organization</flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- Organization Form -->
        <flux:modal name="organization-form" class="w-full max-w-2xl">
            <x-glass-card colorScheme="indigo" class="overflow-hidden">
                <form wire:submit="saveOrganization">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium">Organization Name</label>
                            <flux:input id="name" type="text" wire:model="name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter organization name">
                            </flux:input>
                            @error('name')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium">Description</label>
                            <flux:textarea id="description" wire:model="description"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Brief description of the organization" rows="3">
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
                            {{ $isEditing ? 'Update Organization' : 'Create Organization' }}
                        </flux:button>
                    </div>
                </form>
            </x-glass-card>
        </flux:modal>

        <!-- Organizations List -->
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">
                    <flux:input type="text" wire:model.live.debounce.300ms="searchQuery"
                        class="block w-full rounded-lg border-0 focus:ring-2 focus:ring-indigo-500"
                        placeholder="Search organizations...">
                    </flux:input>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-indigo-300 mr-2">{{ $organizations->total() }} {{
                        Str::plural('organization', $organizations->total()) }}</span>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg">
                <table class="min-w-full divide-y divide-indigo-200/20">
                    <thead class="bg-gradient-to-r from-indigo-900/40 to-indigo-800/30 backdrop-blur-sm">
                        <tr>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Name</span>
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
                                    <span>Created</span>
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
                        @forelse ($organizations as $organization)
                        <tr class="hover:bg-indigo-900/20 transition-colors duration-200">
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full bg-indigo-500/20 text-indigo-200 font-bold text-xl">
                                        {{ substr($organization->name, 0, 1) }}
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-medium text-slate-800 dark:text-slate-100">{{
                                            $organization->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($organization->description)
                                <div class="max-w-xs truncate">{{ $organization->description }}</div>
                                @else
                                <span class="text-slate-400 italic">No description</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center">
                                    <flux:icon name="calendar" class="h-4 w-4 mr-2 text-indigo-400" />
                                    <span>{{ $organization->created_at->format('M d, Y') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <flux:button variant="primary" size="xs"
                                        wire:click="editOrganization({{ $organization->id }})"
                                        class="flex items-center">
                                        <span>Edit</span>
                                    </flux:button>
                                    <flux:button variant="danger" size="xs"
                                        wire:click="deleteOrganization({{ $organization->id }})"
                                        wire:confirm="Are you sure you want to delete this organization?"
                                        class="flex items-center">
                                        <span>Delete</span>
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div
                                    class="flex flex-col items-center justify-center p-6 bg-indigo-900/10 rounded-xl backdrop-blur-sm">
                                    <div
                                        class="w-20 h-20 rounded-full bg-indigo-900/20 flex items-center justify-center mb-4">
                                        <flux:icon name="building-office"
                                            class="w-12 h-12 text-indigo-300 dark:text-indigo-400" />
                                    </div>
                                    <h3 class="text-xl font-medium text-slate-800 dark:text-slate-200">No organizations
                                        found</h3>
                                    <p class="mt-2 text-slate-500 dark:text-slate-400 max-w-sm">Get started by creating
                                        your first organization using the "New Organization" button above.</p>
                                    <flux:modal.trigger name="organization-form" class="mt-4">
                                        <flux:button variant="primary" class="flex items-center">
                                            <flux:icon name="plus" class="h-4 w-4 mr-2" />
                                            Add Your First Organization
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
                {{ $organizations->links() }}
            </div>
        </x-glass-card>

    </div>
</div>