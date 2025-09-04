@props([
'colorScheme' => 'indigo', // Options: indigo, emerald, purple, amber, blue, etc.
'class' => 'p-5'
])

<div {{ $attributes->merge(['class' => "group relative overflow-hidden rounded-2xl border border-{$colorScheme}-100/50
    bg-gradient-to-br from-white to-{$colorScheme}-50/30 p-6 shadow-lg transition-all hover:shadow-xl
    dark:border-{$colorScheme}-900/30 dark:from-neutral-800 dark:to-{$colorScheme}-900/20 {$class}"]) }}>
    <div
        class="absolute left-0 bottom-0 h-20 w-20 translate-y-10 -translate-x-10 transform rounded-full bg-{{ $colorScheme }}-500/10 blur-lg">
    </div>
    <div
        class="absolute right-0 top-0 h-25 w-25 -translate-y-12 translate-x-12 rotate-12 transform rounded-full bg-{{ $colorScheme }}-500/10 blur-lg">
    </div>
    <div class="relative">
        {{ $slot }}
    </div>
</div>