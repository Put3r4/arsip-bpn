@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'helpText' => null,
])

@php
$hasError = $errors->has($name);
$computedValue = old($name, $value);
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $name }}" class="block text-xs font-semibold text-[var(--color-text-main)] mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-[var(--color-danger)] font-bold">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <input 
            type="{{ $type }}" 
            name="{{ $name }}" 
            id="{{ $name }}" 
            value="{{ $computedValue }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge([
                'class' => 'w-full rounded-lg border bg-[var(--color-surface-card)] px-3 py-2 text-sm text-[var(--color-text-main)] shadow-2xs placeholder:text-slate-400 focus:outline-none focus:ring-2 transition ' . 
                ($hasError 
                    ? 'border-[var(--color-danger)] focus:ring-[var(--color-danger)] focus:border-[var(--color-danger)]' 
                    : 'border-[var(--color-surface-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]')
            ]) }}
        />
    </div>

    @if($helpText && !$hasError)
        <p class="mt-1 text-[11px] text-[var(--color-text-muted)]">{{ $helpText }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-xs text-[var(--color-danger)] font-medium">{{ $message }}</p>
    @enderror
</div>
