<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <div class="relative hidden h-full flex-col p-10 text-white lg:flex dark:border-e dark:border-neutral-800">
            <div class="absolute inset-0 bg-neutral-900 overflow-hidden">
                <img src="{{ asset('images/event.jpg') }}" alt="Background image"
                    class="absolute inset-0 w-full h-full object-cover opacity-50" />
            </div>
            <a href="{{ route('home') }}"
                class="relative z-20 flex items-center font-medium transition-all duration-300 hover:scale-105 group"
                wire:navigate>
                <span
                    class="font-extrabold text-6xl tracking-tight bg-gradient-to-r from-indigo-600 via-purple-500 to-pink-500 bg-clip-text text-transparent drop-shadow-lg animate-gradient-x">
                    OS CORPORATION
                </span>
                <div
                    class="absolute -bottom-2 left-0 w-full h-0.5 bg-gradient-to-r from-indigo-600 to-pink-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500 origin-left">
                </div>
                {{-- <div class="absolute -inset-1 -z-10 rounded-lg bg-gradient-to-b from-indigo-500/20 to-purple-500/20 opacity-0 group-hover:opacity-100 blur-xl transition-all duration-500"></div> --}}
            </a>
        </div>
        <div class="w-full lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden"
                    wire:navigate>
                    <span class="flex h-9 w-9 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                    </span>

                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                {{ $slot }}
            </div>
        </div>
    </div>
    @fluxScripts
</body>

</html>
