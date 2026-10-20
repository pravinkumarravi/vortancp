@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
    'footer' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark overflow-hidden']) }}>
    @if($title || $actions)
        <div class="px-6 py-4 border-b border-stroke dark:border-strokedark flex items-center justify-between">
            <div>
                @if($title)
                    <h3 class="text-base font-semibold text-black dark:text-white leading-tight">{{ $title }}</h3>
                @endif
                @if($subtitle)
                    <p class="text-xs text-body dark:text-bodydark mt-0.5">{{ $subtitle }}</p>
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
        <div class="px-6 py-3 bg-[#F7F9FC] dark:bg-[#1A222C]/60 border-t border-stroke dark:border-strokedark">
            {{ $footer }}
        </div>
    @endif
</div>
