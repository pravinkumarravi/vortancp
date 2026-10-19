@props([
    'title',
    'value',
    'subtitle' => null,
    'change' => null,
    'trend' => 'up', // 'up', 'down', 'neutral'
    'icon' => null,
    'progress' => null,
    'gradient' => 'from-emerald-500 to-teal-600',
])

<div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden group hover:border-gray-300 dark:hover:border-slate-700 transition-all">
    <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $title }}</span>
        @if($icon)
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr {{ $gradient }} text-white flex items-center justify-center shadow-md shadow-emerald-500/10 group-hover:scale-105 transition-transform">
                {{ $icon }}
            </div>
        @endif
    </div>

    <div class="flex items-baseline justify-between">
        <h4 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $value }}</h4>
        @if($change)
            <span class="inline-flex items-center text-xs font-semibold {{ $trend === 'up' ? 'text-emerald-600 dark:text-emerald-400' : ($trend === 'down' ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500') }}">
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
        <div class="w-full bg-gray-100 dark:bg-slate-800 rounded-full h-1.5 mt-3 overflow-hidden">
            <div class="bg-gradient-to-r {{ $gradient }} h-1.5 rounded-full" style="width: {{ min(100, max(0, $progress)) }}%"></div>
        </div>
    @endif

    @if($subtitle)
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ $subtitle }}</p>
    @endif
</div>
