@props([
    'variant' => 'primary', // primary, secondary, danger, outline
    'size' => 'md', // sm, md, lg
    'type' => 'button',
    'disabled' => false,
])

@php
$baseClasses = 'inline-flex items-center justify-center font-semibold rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer shadow-xs';

$variantClasses = match($variant) {
    'primary' => 'bg-[var(--color-primary)] hover:bg-[var(--color-primary-strong)] text-[var(--color-primary-contrast)] focus:ring-[var(--color-primary)]',
    'secondary' => 'bg-slate-200 hover:bg-slate-300 text-slate-800 focus:ring-slate-400',
    'danger' => 'bg-[var(--color-danger)] hover:bg-[var(--color-danger-strong)] text-white focus:ring-[var(--color-danger)]',
    'accent' => 'bg-[var(--color-accent)] hover:opacity-90 text-[var(--color-accent-contrast)] focus:ring-[var(--color-accent)]',
    'outline' => 'border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] hover:bg-[var(--color-surface)] text-[var(--color-text-main)] focus:ring-slate-400',
    default => 'bg-[var(--color-primary)] text-white',
};

$sizeClasses = match($size) {
    'sm' => 'px-2.5 py-1.5 text-xs gap-1.5',
    'lg' => 'px-5 py-2.5 text-base gap-2.5',
    default => 'px-3.5 py-2 text-sm gap-2',
};
@endphp

<button 
    type="{{ $type }}" 
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge(['class' => "$baseClasses $variantClasses $sizeClasses"]) }}
>
    {{ $slot }}
</button>
