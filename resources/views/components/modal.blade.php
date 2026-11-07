@props([
    'name',
    'title' => null,
    'maxWidth' => 'lg',
    'footer' => null,
])

@php
$maxWidthClasses = match($maxWidth) {
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
    '4xl' => 'max-w-4xl',
    default => 'max-w-lg',
};
@endphp

<div 
    x-data="{ show: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
    style="display: none;"
>
    <!-- Backdrop Overlay -->
    <div 
        x-show="show" 
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="show = false"
        class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
    ></div>

    <!-- Modal Content Box -->
    <div 
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative w-full {{ $maxWidthClasses }} bg-[var(--color-surface-card)] rounded-2xl border border-[var(--color-surface-border)] shadow-2xl overflow-hidden z-10 my-8 mx-auto"
    >
        @if($title)
            <div class="px-6 py-4 border-b border-[var(--color-surface-border)] flex items-center justify-between">
                <h3 class="text-base font-bold text-[var(--color-text-main)]">{{ $title }}</h3>
                <button 
                    type="button" 
                    @click="show = false" 
                    class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-[var(--color-surface)] transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endif

        <div class="p-6">
            {{ $slot }}
        </div>

        @if($footer)
            <div class="px-6 py-3 border-t border-[var(--color-surface-border)] bg-[var(--color-surface)]/40 flex items-center justify-end gap-2">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
