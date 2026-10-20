@props([
    'title',
    'value',
    'subtitle' => null,
    'change' => null,
    'trend' => 'up', // 'up', 'down', 'neutral'
    'icon' => null,
    'progress' => null,
])

<div class="rounded-xl border border-stroke bg-white p-5.5 shadow-default dark:border-strokedark dark:bg-boxdark relative overflow-hidden group hover:border-brand-500/30 transition-all">
    <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-medium text-body dark:text-bodydark uppercase tracking-wider">{{ $title }}</span>
        @if($icon)
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-[#EFF2F7] dark:bg-[#313D4A] text-brand-500 shadow-xs group-hover:scale-105 transition-transform">
                {{ $icon }}
            </div>
        @endif
    </div>

    <div class="flex items-baseline justify-between">
        <h4 class="text-2xl font-bold tracking-tight text-black dark:text-white">{{ $value }}</h4>
        @if($change)
            <span class="inline-flex items-center text-xs font-semibold {{ $trend === 'up' ? 'text-[#10B981]' : ($trend === 'down' ? 'text-[#DC3545]' : 'text-body') }}">
                @if($trend === 'up')
                    <svg class="w-3.5 h-3.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                @elseif($trend === 'down')
                    <svg class="w-3.5 h-3.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                @endif
                {{ $change }}
            </span>
        @endif
    </div>

    @if($progress !== null)
        <div class="w-full bg-[#E2E8F0] dark:bg-[#313D4A] rounded-full h-1.5 mt-3 overflow-hidden">
            <div class="bg-brand-500 h-1.5 rounded-full" style="width: {{ min(100, max(0, $progress)) }}%"></div>
        </div>
    @endif

    @if($subtitle)
        <p class="text-xs text-body dark:text-bodydark mt-2">{{ $subtitle }}</p>
    @endif
</div>
