@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
    'footer' => null,
    'noPadding' => false,
])

<div {{ $attributes->merge(['class' => 'bg-[var(--color-surface-card)] rounded-xl border border-[var(--color-surface-border)] shadow-xs overflow-hidden']) }}>
    @if($title || $actions)
        <div class="px-5 py-4 border-b border-[var(--color-surface-border)] flex items-center justify-between flex-wrap gap-2">
            <div>
                @if($title)
                    <h3 class="text-base font-bold text-[var(--color-text-main)] tracking-tight">{{ $title }}</h3>
                @endif
                @if($subtitle)
                    <p class="text-xs text-[var(--color-text-muted)] mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            @if($actions)
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $noPadding ? '' : 'p-5' }}">
        {{ $slot }}
    </div>

    @if($footer)
        <div class="px-5 py-3 border-t border-[var(--color-surface-border)] bg-[var(--color-surface)]/50 text-xs text-[var(--color-text-muted)]">
            {{ $footer }}
        </div>
    @endif
</div>
