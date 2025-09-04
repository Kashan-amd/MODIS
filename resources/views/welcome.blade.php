<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body
    class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
    <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden">
        @if (Route::has('login'))
            <nav class="flex items-center justify-end gap-4">
                @auth
                    <a href="{{ url('/dashboard') }}"
                        class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal">
                        Log in
                    </a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                            Register
                        </a>
                    @endif
                @endauth
            </nav>
        @endif
    </header>
    <div
        class="flex items-center justify-center w-full transition-all opacity-100 duration-750 lg:grow starting:opacity-0 animate-fadeIn">
        <main
            class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row shadow-2xl rounded-lg overflow-hidden transform hover:scale-[1.01] transition-transform duration-300">
            <div
                class="text-[15px] leading-relaxed flex-1 p-8 pb-12 lg:p-20 bg-white dark:bg-[#161615] dark:text-[#EDEDEC] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] rounded-es-lg rounded-ee-lg lg:rounded-ss-lg lg:rounded-ee-none backdrop-blur-sm">
                <h1
                    class="mb-4 font-bold text-5xl bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">
                    MODIS</h1>
            </div>
            <div
                class="bg-[#fff2f2] dark:bg-[#1D0002] relative lg:-ms-px -mb-px lg:mb-0 rounded-t-lg lg:rounded-t-none lg:rounded-e-lg lg:aspect-auto w-full lg:w-[438px] shrink-0 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent z-10"></div>
                <img src="{{ asset('images/event.jpg') }}" alt="MODIS"
                    class="absolute inset-0 w-full h-full object-cover transform hover:scale-110 transition-transform duration-700 ease-in-out">
                <div class="absolute bottom-6 left-6 z-20 text-white">
                    <h2 class="text-xl font-bold drop-shadow-md">Discover Possibilities</h2>
                    <p class="text-sm opacity-90">Innovation starts here</p>
                </div>
            </div>
        </main>
    </div>

    @if (Route::has('login'))
        <div class="h-14.5 hidden lg:block"></div>
    @endif
    @fluxScripts
</body>

</html>
