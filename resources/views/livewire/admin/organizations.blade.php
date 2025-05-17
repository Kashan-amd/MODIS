<?php

use Livewire\Volt\Component;
use App\Models\Organization;
use Livewire\WithPagination;
use Illuminate\Support\Collection;

new class extends Component {
    use WithPagination;

    public string $name = '';
    public string $description = '';
    public bool $showModal = false;
    public ?Organization $editingOrganization = null;

    public function mount(): void
    {
        $this->resetForm();
    }

    public function with(): array
    {
        return [
            'organizations' => Organization::latest()->paginate(10),
        ];
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->description = '';
        $this->editingOrganization = null;
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|min:2|max:255',
            'description' => 'nullable|max:255',
        ]);

        if ($this->editingOrganization) {
            $this->editingOrganization->update([
                'name' => $this->name,
                'description' => $this->description,
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Organization updated successfully!',
            ]);
        } else {
            Organization::create([
                'name' => $this->name,
                'description' => $this->description,
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Organization created successfully!',
            ]);
        }

        $this->closeModal();
    }

    public function edit(Organization $organization): void
    {
        $this->editingOrganization = $organization;
        $this->name = $organization->name;
        $this->description = $organization->description ?? '';
        $this->showModal = true;
    }

    public function delete(Organization $organization): void
    {
        $organization->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Organization deleted successfully!',
        ]);
    }
}; ?>

<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

    <!-- Page header -->
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <!-- Left: Title -->
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-slate-100">Organizations</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Manage your organizations and their details</p>
        </div>

        <!-- Right: Actions -->
        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
            <!-- Add organization button -->
            <flux:button color="primary" @click="$wire.openModal()">
                <flux:icon name="plus" class="w-4 h-4 mr-2" />
                Add Organization
            </flux:button>
        </div>
    </div>

    <!-- Organization cards / table -->
    <x-glass-card colorScheme="indigo" class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="text-left bg-slate-50 dark:bg-indigo-900/20">
                        <th class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Name</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Description</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Created</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse ($organizations as $organization)
                    <tr class="hover:bg-slate-50 dark:hover:bg-indigo-900/30">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-800 dark:text-slate-100">{{ $organization->name }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400">
                            {{ $organization->description ?? 'No description' }}
                        </td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-500">
                            {{ $organization->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-3 text-right space-x-1">
                            <flux:button variant="primary" size="xs" @click="$wire.edit({{ $organization->id }})">
                                <flux:icon name="pencil" class="w-4 h-4" />
                            </flux:button>

                            <flux:button variant="danger" size="xs" x-data=""
                                @click="$wire.delete({{ $organization->id }})">
                                <flux:icon name="trash" class="w-4 h-4" />
                            </flux:button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                            <div class="flex flex-col items-center justify-center">
                                <flux:icon name="building-office"
                                    class="w-12 h-12 text-slate-300 dark:text-slate-600 mb-4" />
                                <h3 class="text-lg font-medium">No organizations found</h3>
                                <p class="mt-1">Get started by creating your first organization</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
            {{ $organizations->links() }}
        </div>
    </x-glass-card>

    <!-- Add/Edit Organization Modal -->
    <flux:modal wire:model="showModal" class="w-5xl">
        <x-glass-card colorScheme="indigo" class="p-5">
            <form wire:submit="save">
                <div class="space-y-4">
                    <!-- Name -->
                    <div>
                        <flux:heading for="name" value="Name" />
                        <flux:input id="name" type="text" class="mt-1 block w-full" wire:model="name"
                            placeholder="Organization name" required autofocus />
                        {{--
                        <flux:error :messages="$errors->get('name')" class="mt-2" /> --}}
                    </div>

                    <!-- Description -->
                    <div>
                        <flux:heading for="description" value="Description" />
                        <flux:textarea id="description" class="mt-1 block w-full" wire:model="description"
                            placeholder="Brief description of the organization" rows="3" />
                        {{--
                        <flux:error :messages="$errors->get('description')" class="mt-2" /> --}}
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <flux:button type="button" variant="danger" @click="$wire.closeModal()">
                        Cancel
                    </flux:button>

                    <flux:button type="submit" variant="primary">
                        {{ $editingOrganization ? 'Update Organization' : 'Create Organization' }}
                    </flux:button>
                </div>
            </form>
        </x-glass-card>
    </flux:modal>
</div>