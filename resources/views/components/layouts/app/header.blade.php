<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('dashboard') }}" :current="request()->routeIs('dashboard')" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0" wire:navigate>
                <x-app-logo />
            </a>

            <!-- Primary Navigation (desktop) -->
            <flux:navbar class="-mb-px max-lg:hidden">
                <!--<flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>-->

                <!-- Admin Dropdown -->
                <flux:dropdown position="bottom" align="start" class="ms-3">
                    <flux:navbar.item icon="building-office" :label="__('Admin')" class="cursor-pointer" :current="request()->routeIs('admin.*')">
                        {{ __('Admin') }}
                    </flux:navbar.item>
                    <flux:menu class="w-64">
                        <flux:menu.radio.group>
                            <flux:menu.item icon="building-office" :href="route('admin.organizations')" wire:navigate>
                                {{ __('Organizations') }}
                            </flux:menu.item>
                            <flux:menu.item icon="building-library">
                                {{ __('Banks') }}
                            </flux:menu.item>
                            <flux:menu.item icon="user-group">
                                {{ __('Employees') }}
                            </flux:menu.item>
                            <flux:menu.item icon="users" :href="route('admin.clients')" wire:navigate>
                                {{ __('Clients') }}
                            </flux:menu.item>
                            <flux:menu.item icon="building-storefront" :href="route('admin.vendors')" wire:navigate>
                                {{ __('Vendors') }}
                            </flux:menu.item>
                            <flux:menu.item icon="cube" :href="route('admin.items')" wire:navigate>
                                {{ __('Items') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>
                    </flux:menu>
                </flux:dropdown>

                <!-- Sales Dropdown -->
                <flux:dropdown position="bottom" align="start" class="ms-2">
                    <flux:navbar.item icon="clipboard-document-list" :label="__('Sales')" class="cursor-pointer" :current="request()->routeIs('sales.*')">
                        {{ __('Sales') }}
                    </flux:navbar.item>
                    <flux:menu class="w-48">
                        <flux:menu.radio.group>
                            <flux:menu.item icon="clipboard-document-list" :href="route('sales.job-booking')" wire:navigate>
                                {{ __('Job Booking') }}
                            </flux:menu.item>
                            <flux:menu.item icon="calculator" :href="route('sales.costing')" wire:navigate>
                                {{ __('Costing') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>
                    </flux:menu>
                </flux:dropdown>

                <!-- Accounts Dropdown -->
                <flux:dropdown position="bottom" align="start" class="ms-2">
                    <flux:navbar.item icon="users" :label="__('Accounts')" class="cursor-pointer" :current="request()->routeIs('ledgers.*')">
                        {{ __('Accounts') }}
                    </flux:navbar.item>
                    <flux:menu class="w-48">
                        <flux:menu.radio.group>
                            <flux:menu.item icon="users">
                                {{ __('Client Ledger') }}
                            </flux:menu.item>
                            <flux:menu.item icon="building-storefront">
                                {{ __('Vendor Ledger') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>
                    </flux:menu>
                </flux:dropdown>

                <!-- Financial Dropdown -->
                <flux:dropdown position="bottom" align="start" class="ms-2">
                    <flux:navbar.item icon="chart-bar-square" :label="__('Financial')" class="cursor-pointer" :current="request()->routeIs('accounts.*')">
                        {{ __('Financial') }}
                    </flux:navbar.item>
                    <flux:menu class="w-56">
                        <flux:menu.radio.group>
                            <flux:menu.item icon="chart-bar-square" :href="route('accounts.chart-of-accounts')" wire:navigate>
                                {{ __('Chart of Accounts') }}
                            </flux:menu.item>
                            <flux:menu.item icon="book-open" :href="route('accounts.transaction-entries')" wire:navigate>
                                {{ __('Transactions') }}
                            </flux:menu.item>
                            <flux:menu.item icon="banknotes" :href="route('accounts.petty-cash')" wire:navigate>
                                {{ __('Petty Cash') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>
                    </flux:menu>
                </flux:dropdown>

                <flux:navbar.item icon="document-chart-bar" :href="route('financial-reports')" :current="request()->routeIs('financial-reports')" wire:navigate>
                    {{ __('Reports') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <!-- Desktop User Menu (mirrors sidebar's richer profile) -->
            <flux:dropdown position="top" align="end">
                <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()" icon-trailing="chevrons-up-down" />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
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

        <!-- Mobile Menu -->
        <flux:sidebar stashable sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="ms-1 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')">
                    <flux:navlist.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist>
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
