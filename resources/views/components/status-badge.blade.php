@props([
    'status' => 'active',
    'label' => null,
])

@php
    $normalized = strtolower((string) $status);
    $text = $label ?? ucfirst($normalized);

    $styles = match($normalized) {
        'active', 'enabled', 'running', 'success', 'issued', 'ok', 'online' => [
            'class' => 'bg-[#E1F9EE] text-[#0A9952] dark:bg-[#0A9952]/15 dark:text-[#3CD856] border-[#0A9952]/20',
            'dot' => 'bg-[#0A9952]',
        ],
        'pending', 'queued', 'warning', 'in_progress', 'syncing' => [
            'class' => 'bg-[#FEF8EC] text-[#D38B0B] dark:bg-[#D38B0B]/15 dark:text-[#FFBA00] border-[#D38B0B]/20',
            'dot' => 'bg-[#D38B0B]',
        ],
        'failed', 'error', 'danger', 'blocked', 'banned', 'offline', 'critical' => [
            'class' => 'bg-[#FEECEC] text-[#EA2B2B] dark:bg-[#EA2B2B]/15 dark:text-[#FB5454] border-[#EA2B2B]/20',
            'dot' => 'bg-[#EA2B2B]',
        ],
        'suspended', 'disabled', 'inactive' => [
            'class' => 'bg-[#EFF2F7] text-[#64748B] dark:bg-[#313D4A] dark:text-[#AEB7C0] border-stroke dark:border-strokedark',
            'dot' => 'bg-[#8A99AD]',
        ],
        default => [
            'class' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 border-brand-200 dark:border-brand-500/20',
            'dot' => 'bg-brand-500',
        ],
    };
@endphp

<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $styles['class'] }}">
    <span class="w-1.5 h-1.5 rounded-full {{ $styles['dot'] }}"></span>
    <span>{{ $text }}</span>
</span>
