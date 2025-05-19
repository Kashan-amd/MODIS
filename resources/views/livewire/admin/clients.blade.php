<?php

use Livewire\Volt\Component;
use App\Models\Client;
use Livewire\WithPagination;
use Illuminate\Support\Collection;

new class extends Component {
    use WithPagination;

    public $name = '';
    public $business_name = '';
    public $contact_number = '';
    public $contact_person = '';
    public $address = '';
    public $bm_official = '';
    public $editingClientId = null;
    public $isEditing = false;
    public $searchQuery = '';

    public function rules()
    {
        return [
            'name' => 'required|min:2|max:255',
            'business_name' => 'nullable|max:255',
            'contact_number' => 'nullable|max:20',
            'contact_person' => 'nullable|max:255',
            'address' => 'nullable|max:255',
            'bm_official' => 'nullable|max:255',
        ];
    }

    public function saveClient()
    {
        $this->validate();

        if ($this->isEditing) {
            $client = Client::find($this->editingClientId);
            $client->update([
                'name' => $this->name,
                'business_name' => $this->business_name,
                'contact_number' => $this->contact_number,
                'contact_person' => $this->contact_person,
                'address' => $this->address,
                'bm_official' => $this->bm_official,
            ]);

            $this->dispatch('client-updated', 'Client updated successfully');
        } else {
            Client::create([
                'name' => $this->name,
                'business_name' => $this->business_name,
                'contact_number' => $this->contact_number,
                'contact_person' => $this->contact_person,
                'address' => $this->address,
                'bm_official' => $this->bm_official,
            ]);

            $this->dispatch('client-created', 'Client created successfully');
        }

        $this->resetForm();
        $this->modal('client-form')->close();
    }

    public function editClient($clientId)
    {
        $this->isEditing = true;
        $this->editingClientId = $clientId;

        $client = Client::find($clientId);
        $this->name = $client->name;
        $this->business_name = $client->business_name ?? '';
        $this->contact_number = $client->contact_number ?? '';
        $this->contact_person = $client->contact_person ?? '';
        $this->address = $client->address ?? '';
        $this->bm_official = $client->bm_official ?? '';

        $this->modal('client-form')->show();
    }

    public function deleteClient($clientId)
    {
        Client::destroy($clientId);
        $this->dispatch('client-deleted', 'Client deleted successfully');
    }

    public function resetForm()
    {
        $this->reset(['name', 'business_name', 'contact_number', 'contact_person', 'address', 'bm_official', 'editingClientId', 'isEditing']);
        $this->resetValidation();
    }

    public function cancelEdit()
    {
        $this->resetForm();
        $this->modal('client-form')->close();
    }

    public function with(): array
    {
        $query = Client::query()
            ->when($this->searchQuery, function ($query, $search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('business_name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc');

        return [
            'clients' => $query->paginate(10),
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
                    <flux:icon name="identification" class="h-10 w-10" />
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-slate-100">Clients</h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Manage your clients and their contact information</p>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Add client button -->
                <flux:modal.trigger name="client-form">
                    <flux:button variant="primary">New Client</flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- Client Form -->
        <flux:modal name="client-form" class="w-full max-w-2xl">
            <x-glass-card colorScheme="indigo" class="overflow-hidden">
                <form wire:submit="saveClient">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium">Client Name</label>
                            <flux:input id="name" type="text" wire:model="name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter client name">
                            </flux:input>
                            @error('name')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="business_name" class="block text-sm font-medium">Business Name</label>
                            <flux:input id="business_name" type="text" wire:model="business_name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter business name">
                            </flux:input>
                            @error('business_name')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="contact_person" class="block text-sm font-medium">Contact Person</label>
                                <flux:input id="contact_person" type="text" wire:model="contact_person"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Enter contact person">
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

                        <div>
                            <label for="bm_official" class="block text-sm font-medium">BM Official</label>
                            <flux:input id="bm_official" type="text" wire:model="bm_official"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter BM official">
                            </flux:input>
                            @error('bm_official')
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
                            {{ $isEditing ? 'Update Client' : 'Create Client' }}
                        </flux:button>
                    </div>
                </form>
            </x-glass-card>
        </flux:modal>

        <!-- Clients List -->
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">
                    <flux:input type="text" wire:model.live.debounce.300ms="searchQuery"
                        class="block w-full rounded-lg border-0 focus:ring-2 focus:ring-indigo-500"
                        placeholder="Search clients...">
                    </flux:input>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-indigo-300 mr-2">{{ $clients->total() }} {{ Str::plural('client',
                        $clients->total()) }}</span>
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
                                    <span>Business Name</span>
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
                                <div class="flex items-center justify-end space-x-1">
                                    <span>Actions</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-indigo-900/10 backdrop-blur-sm divide-y divide-indigo-200/10">
                        @forelse ($clients as $client)
                        <tr class="hover:bg-indigo-900/20 transition-colors duration-200">
                            <td class="px-6 py-4 text-sm">
                                <div>
                                    <div class="font-medium text-slate-800 dark:text-slate-100">{{ $client->name }}
                                    </div>
                                    @if($client->address)
                                    <div class="text-slate-500 dark:text-slate-400 text-xs flex items-center mt-1">
                                        <flux:icon name="map-pin" class="h-3 w-3 mr-1" />
                                        {{ $client->address }}
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center">
                                    @if($client->business_name)
                                    <span
                                        class="px-2 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-200 text-xs">
                                        {{ $client->business_name }}
                                    </span>
                                    @else
                                    <span class="text-slate-400 italic">Not provided</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div>
                                    @if($client->contact_person)
                                    <div class="font-medium">{{ $client->contact_person }}</div>
                                    @else
                                    <span class="text-slate-400 italic">Not provided</span>
                                    @endif

                                    @if($client->bm_official)
                                    <div class="text-slate-500 dark:text-indigo-300 text-xs flex items-center mt-1">
                                        <span
                                            class="px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-200 text-xs">
                                            BM: {{ $client->bm_official }}
                                        </span>
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($client->contact_number)
                                <div class="flex items-center">
                                    <span>{{ $client->contact_number }}</span>
                                </div>
                                @else
                                <span class="text-slate-400 italic">Not provided</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <flux:button variant="primary" size="xs" wire:click="editClient({{ $client->id }})"
                                        class="flex items-center">

                                        <span>Edit</span>
                                    </flux:button>
                                    <flux:button variant="danger" size="xs" wire:click="deleteClient({{ $client->id }})"
                                        wire:confirm="Are you sure you want to delete this client?"
                                        class="flex items-center">

                                        <span>Delete</span>
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div
                                    class="flex flex-col items-center justify-center p-6 bg-indigo-900/10 rounded-xl backdrop-blur-sm">
                                    <div
                                        class="w-20 h-20 rounded-full bg-indigo-900/20 flex items-center justify-center mb-4">
                                        <flux:icon name="users"
                                            class="w-12 h-12 text-indigo-300 dark:text-indigo-400" />
                                    </div>
                                    <h3 class="text-xl font-medium text-slate-800 dark:text-slate-200">No clients found
                                    </h3>
                                    <p class="mt-2 text-slate-500 dark:text-slate-400 max-w-sm">Get started by creating
                                        your first client using the "New Client" button above.</p>
                                    <flux:modal.trigger name="client-form" class="mt-4">
                                        <flux:button variant="primary" class="flex items-center">
                                            <flux:icon name="plus" class="h-4 w-4 mr-2" />
                                            Add Your First Client
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
                {{ $clients->links() }}
            </div>
        </x-glass-card>

    </div>
</div>