@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'target' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-boxdark disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer';

    $sizeClasses = match($size) {
        'sm' => 'px-3 py-1.5 text-xs gap-1.5',
        'lg' => 'px-6 py-3 text-base gap-2.5',
        default => 'px-4 py-2 text-sm gap-2',
    };

    $variantClasses = match($variant) {
        'secondary' => 'bg-gray-100 hover:bg-gray-200 dark:bg-[#313D4A] dark:hover:bg-opacity-90 text-gray-800 dark:text-white focus:ring-gray-400',
        'danger' => 'bg-[#DC3545] hover:bg-opacity-90 text-white focus:ring-[#DC3545] shadow-sm',
        'outline' => 'border border-stroke dark:border-strokedark hover:bg-gray-100 dark:hover:bg-[#313D4A] text-gray-800 dark:text-white focus:ring-brand-500',
        default => 'bg-brand-500 hover:bg-brand-600 text-white focus:ring-brand-500 shadow-sm shadow-brand-500/20',
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
