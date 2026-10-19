@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'target' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-xl transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer';

    $sizeClasses = match($size) {
        'sm' => 'px-3 py-1.5 text-xs gap-1.5',
        'lg' => 'px-6 py-3 text-base gap-2.5',
        default => 'px-4 py-2 text-sm gap-2',
    };

    $variantClasses = match($variant) {
        'secondary' => 'bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-gray-700 dark:text-gray-200 focus:ring-slate-500',
        'danger' => 'bg-rose-600 hover:bg-rose-500 text-white focus:ring-rose-500 shadow-md shadow-rose-600/20',
        'outline' => 'border border-gray-300 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 text-gray-700 dark:text-gray-200 focus:ring-slate-500',
        default => 'bg-emerald-600 hover:bg-emerald-500 text-white focus:ring-emerald-500 shadow-md shadow-emerald-600/20',
    };
@endphp

<button 
    type="{{ $type }}" 
    @if($target)
        wire:loading.attr="disabled"
        wire:target="{{ $target }}"
    @endif
    {{ $attributes->merge(['class' => "$baseClasses $sizeClasses $variantClasses"]) }}
>
    @if($target)
        <svg wire:loading wire:target="{{ $target }}" class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @endif
    {{ $slot }}
</button>
