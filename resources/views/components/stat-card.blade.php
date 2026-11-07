@props([
    'title',
    'value',
    'icon' => null,
    'description' => null,
    'variant' => 'primary', // primary, danger, accent, warning, success
    'href' => null,
])

@php
$variantClasses = match($variant) {
    'danger' => 'border-rose-200 bg-rose-50/50 text-rose-900',
    'warning' => 'border-amber-200 bg-amber-50/50 text-amber-900',
    'success' => 'border-emerald-200 bg-emerald-50/50 text-emerald-900',
    'accent' => 'border-sky-200 bg-sky-50/50 text-sky-900',
    default => 'border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)]',
};

$iconBgClasses = match($variant) {
    'danger' => 'bg-rose-100 text-rose-700',
    'warning' => 'bg-amber-100 text-amber-700',
    'success' => 'bg-emerald-100 text-emerald-700',
    'accent' => 'bg-sky-100 text-sky-800',
    default => 'bg-[var(--color-surface)] text-[var(--color-primary)]',
};
@endphp

<div class="col-span-12 sm:col-span-6 md:col-span-4 rounded-xl border p-5 shadow-xs transition hover:shadow-md {{ $variantClasses }}">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-[var(--color-text-muted)]">{{ $title }}</p>
            <p class="text-3xl font-black mt-2 tracking-tight">{{ $value }}</p>
            @if($description)
                <p class="text-xs text-[var(--color-text-muted)] mt-1.5">{{ $description }}</p>
            @endif
        </div>
        @if($icon)
            <div class="w-12 h-12 rounded-xl flex items-center justify-center {{ $iconBgClasses }}">
                {{ $icon }}
            </div>
        @endif
    </div>

    @if($href)
        <div class="mt-4 pt-3 border-t border-black/5 flex items-center justify-between text-xs font-semibold">
            <a href="{{ $href }}" class="hover:underline flex items-center gap-1">
                <span>Lihat rincian</span>
                <span>&rarr;</span>
            </a>
        </div>
    @endif
</div>
