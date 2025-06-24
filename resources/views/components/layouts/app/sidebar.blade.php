<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <!-- Desktop User Menu -->
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">
            <flux:navlist.group class="grid">
                <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
            </flux:navlist.group>
        </flux:navlist>

        <flux:navlist variant="outline">

            <flux:navlist.group expandable :heading="__('Admin')" class="hidden lg:grid" :expanded="true">
                <flux:navlist.item icon="building-office" :href="route('admin.organizations')">Organizations
                </flux:navlist.item>
                <flux:navlist.item icon="users" :href="route('admin.clients')">Clients</flux:navlist.item>
                <flux:navlist.item icon="building-storefront" :href="route('admin.vendors')">Vendors
                </flux:navlist.item>
                <flux:navlist.item icon="cube" :href="route('admin.items')">Items
                </flux:navlist.item>
                <flux:navlist.item icon="banknotes" :href="route('admin.opening-balances')">Opening Balances
                </flux:navlist.item>
                {{-- <flux:navlist.item icon="circle-stack" href="#">Banks</flux:navlist.item> --}}
            </flux:navlist.group>

            <flux:navlist.group expandable :heading="__('Jobs')" class="hidden lg:grid" :expanded="true">
                <flux:navlist.item icon="clipboard-document-list" :href="route('sales.job-booking')">Job Booking
                </flux:navlist.item>
                <flux:navlist.item icon="calculator" :href="route('sales.costing')">Costing</flux:navlist.item>
            </flux:navlist.group>

            <flux:navlist.group expandable :heading="__('Accounts')" class="hidden lg:grid" :expanded="true">
                <flux:navlist.item icon="building-storefront" href="#">Vendor Ledger</flux:navlist.item>
                <flux:navlist.item icon="users" href="#">Client Ledger</flux:navlist.item>
            </flux:navlist.group>

            <flux:navlist.group expandable :heading="__('Finance')" class="hidden lg:grid" :expanded="true">
                <flux:navlist.item icon="credit-card" :href="route('accounts.transactions')" wire:navigate>
                    {{ __('Transactions') }}
                </flux:navlist.item>
                <flux:navlist.item icon="chart-bar-square" :href="route('accounts.chart-of-accounts')" wire:navigate>
                    {{ __('Chart of Accounts') }}
                </flux:navlist.item>
                <flux:navlist.item icon="book-open" :href="route('accounts.journal-entries')" wire:navigate>
                    {{ __('Journal Entries') }}
                </flux:navlist.item>
                <flux:navlist.item icon="banknotes" :href="route('accounts.petty-cash')" wire:navigate>
                    {{ __('Petty Cash') }}
                </flux:navlist.item>
            </flux:navlist.group>

            <flux:navlist.group expandable :heading="__('Reports')" class="hidden lg:grid" :expanded="true">
                <flux:navlist.item icon="document-chart-bar" :href="route('accounts.financial-reports')" wire:navigate>
                    {{ __('Financial Reports') }}
                </flux:navlist.item>
            </flux:navlist.group>

        </flux:navlist>

        <flux:spacer />

        <!-- Desktop User Menu -->
        <flux:dropdown position="bottom" align="start">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                icon-trailing="chevrons-up-down" />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @fluxScripts
</body>

</html>
