<?php

use Livewire\Volt\Component;
use App\Models\OpeningBalance;
use App\Models\Organization;
use Livewire\WithPagination;
use Illuminate\Support\Collection;

new class extends Component {
    use WithPagination;

    public $organization_id = '';
    public $amount = '';
    public $type = '';
    public $date = '';
    public $description = '';
    public $editingBalanceId = null;
    public $isEditing = false;
    public $searchQuery = '';

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
        $this->type = 'debit'; // Set default type
    }

    public function rules()
    {
        return [
            'organization_id' => 'required|exists:organizations,id',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:debit,credit',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
        ];
    }

    public function saveOpeningBalance()
    {
        $this->validate();

        if ($this->isEditing) {
            $openingBalance = OpeningBalance::find($this->editingBalanceId);
            $openingBalance->update([
                'organization_id' => $this->organization_id,
                'amount' => $this->amount,
                'date' => $this->date,
                'description' => $this->description,
                'type' => $this->type,
            ]);

            $this->dispatch('balance-updated', 'Opening balance updated successfully');
        } else {
            OpeningBalance::create([
                'organization_id' => $this->organization_id,
                'amount' => $this->amount,
                'date' => $this->date,
                'description' => $this->description,
                'type' => $this->type,
            ]);

            $this->dispatch('balance-created', 'Opening balance created successfully');
        }

        $this->resetForm();
        $this->modal('balance-form')->close();
    }

    public function editOpeningBalance($balanceId)
    {
        $this->isEditing = true;
        $this->editingBalanceId = $balanceId;

        $openingBalance = OpeningBalance::find($balanceId);
        $this->organization_id = $openingBalance->organization_id;
        $this->amount = $openingBalance->amount;
        $this->date = $openingBalance->date;
        $this->description = $openingBalance->description ?? '';
        $this->type = $openingBalance->type;

        $this->modal('balance-form')->show();
    }

    public function deleteOpeningBalance($balanceId)
    {
        OpeningBalance::destroy($balanceId);
        $this->dispatch('balance-deleted', 'Opening balance deleted successfully');
    }

    public function resetForm()
    {
        $this->reset(['organization_id', 'amount', 'description', 'editingBalanceId', 'isEditing']);
        $this->date = now()->format('Y-m-d');
        $this->type = 'debit'; // Set default type
        $this->resetValidation();
    }

    public function cancelEdit()
    {
        $this->resetForm();
        $this->modal('balance-form')->close();
    }

    public function with(): array
    {
        $query = OpeningBalance::query()
            ->when($this->searchQuery, function ($query, $search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery
                        ->where('amount', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhereHas('organization', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('date', 'desc');

        return [
            'openingBalances' => $query->paginate(10),
            'organizations' => Organization::orderBy('name')->get(),
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
                    <flux:icon name="banknotes" class="h-10 w-10" />
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-slate-100">Opening Balances
                    </h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Manage organization opening balances</p>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Add opening balance button -->
                <flux:modal.trigger name="balance-form">
                    <flux:button variant="primary" class="flex items-center">
                        <flux:icon name="plus" class="h-4 w-4 mr-2" />
                        <span>New Opening Balance</span>
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- Opening Balance Form -->
        <flux:modal name="balance-form" class="w-full max-w-2xl">
            <x-glass-card colorScheme="indigo" class="overflow-hidden">
                <form wire:submit="saveOpeningBalance">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="organization_id" class="block text-sm font-medium">Organization</label>
                            <flux:select id="organization_id" wire:model="organization_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <flux:select.option value="">Select organization</flux:select.option>
                                @foreach ($organizations as $organization)
                                <flux:select.option value="{{ $organization->id }}">{{ $organization->name }}
                                </flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('organization_id')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium">Type</label>
                            <flux:select id="type" wire:model="type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <flux:select.option value="">Select type</flux:select.option>
                                <flux:select.option value="debit">Debit</flux:select.option>
                                <flux:select.option value="credit">Credit</flux:select.option>
                            </flux:select>
                            @error('type')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="amount" class="block text-sm font-medium">Amount</label>
                                <flux:input id="amount" type="number" step="0.01" min="0" wire:model="amount"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Enter amount">
                                </flux:input>
                                @error('amount')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="date" class="block text-sm font-medium">Date</label>
                                <flux:input id="date" type="date" wire:model="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </flux:input>
                                @error('date')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium">Description</label>
                            <flux:textarea id="description" wire:model="description"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter description" rows="2">
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
                            {{ $isEditing ? 'Update Opening Balance' : 'Create Opening Balance' }}
                        </flux:button>
                    </div>
                </form>
            </x-glass-card>
        </flux:modal>

        <!-- Opening Balances List -->
        <x-glass-card colorScheme="indigo" class="backdrop-blur-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">
                    <flux:input type="text" wire:model.live.debounce.300ms="searchQuery"
                        class="block w-full rounded-lg border-0 focus:ring-2 focus:ring-indigo-500"
                        placeholder="Search opening balances...">
                    </flux:input>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg">
                <table class="min-w-full divide-y divide-indigo-200/20">
                    <thead class="bg-indigo-900/50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Organization</span>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-100">
                                <div class="flex items-center space-x-1">
                                    <span>Amount</span>
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
                                    <span>Date</span>
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
                        @forelse ($openingBalances as $balance)
                        <tr class="hover:bg-indigo-900/20 transition-colors duration-200">
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium text-indigo-100">{{ $balance->organization->name }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-200">
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-emerald-500/20 text-emerald-200">
                                    <flux:icon name="banknotes" class="inline-block h-3 w-3 mr-1" />
                                    PKR {{ number_format($balance->amount, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-200">
                                @if($balance->type == 'credit')
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-purple-500/20 text-purple-200">
                                    <flux:icon name="arrow-left" class="inline-block h-3 w-3 mr-1" />
                                    Credit
                                </span>
                                @else
                                <span
                                    class="px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-blue-500/20 text-blue-200">
                                    <flux:icon name="arrow-right" class="inline-block h-3 w-3 mr-1" />
                                    Debit
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-200">
                                {{ date('M d, Y', strtotime($balance->date)) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-200">
                                <div class="truncate max-w-xs">{{ $balance->description ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end items-center space-x-2">
                                    <flux:button size="xs" variant="primary"
                                        wire:click="editOpeningBalance({{ $balance->id }})">
                                        Edit
                                    </flux:button>
                                    <flux:button size="xs" variant="danger"
                                        wire:confirm="Are you sure you want to delete this opening balance?"
                                        wire:click="deleteOpeningBalance({{ $balance->id }})">
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
                                        <flux:icon name="banknotes"
                                            class="w-12 h-12 text-indigo-300 dark:text-indigo-400" />
                                    </div>
                                    <h3 class="text-xl font-medium text-slate-800 dark:text-slate-200">No opening
                                        balances found</h3>
                                    <p class="mt-2 text-slate-500 dark:text-slate-400 max-w-sm">Get started by creating
                                        your first opening balance using the "New Opening Balance" button above.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $openingBalances->links() }}
            </div>
        </x-glass-card>

    </div>
</div>