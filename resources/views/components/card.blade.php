@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
    'footer' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden']) }}>
    @if($title || $actions)
        <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                @if($title)
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white leading-tight">{{ $title }}</h3>
                @endif
                @if($subtitle)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            @if($actions)
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>

    @if($footer)
        <div class="px-6 py-3 bg-gray-50 dark:bg-slate-950/40 border-t border-gray-100 dark:border-slate-800">
            {{ $footer }}
        </div>
    @endif
</div>
