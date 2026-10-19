@props([
    'status' => 'active',
    'label' => null,
])

@php
    $normalized = strtolower((string) $status);
    $text = $label ?? ucfirst($normalized);

    $styles = match($normalized) {
        'active', 'enabled', 'running', 'success' => [
            'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/60',
            'dot' => 'bg-emerald-500 animate-pulse',
        ],
        'pending', 'queued', 'warning' => [
            'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400 border-amber-200 dark:border-amber-800/60',
            'dot' => 'bg-amber-500 animate-spin',
        ],
        'failed', 'error', 'danger' => [
            'class' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400 border-rose-200 dark:border-rose-800/60',
            'dot' => 'bg-rose-500',
        ],
        'suspended', 'disabled', 'inactive' => [
            'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700',
            'dot' => 'bg-slate-400',
        ],
        default => [
            'class' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-400 border-blue-200 dark:border-blue-800/60',
            'dot' => 'bg-blue-500',
        ],
    };
@endphp

<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $styles['class'] }}">
    <span class="w-1.5 h-1.5 rounded-full {{ $styles['dot'] }}"></span>
    <span>{{ $text }}</span>
</span>
