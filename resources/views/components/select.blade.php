@props([
    'label' => null,
    'name',
    'required' => false,
    'placeholder' => '-- Pilih Opsi --',
    'helpText' => null,
    'options' => [],
    'selected' => null,
])

@php
$hasError = $errors->has($name);
$selectedValue = old($name, $selected);
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
        <select 
            name="{{ $name }}" 
            id="{{ $name }}" 
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge([
                'class' => 'w-full rounded-lg border bg-[var(--color-surface-card)] px-3 py-2 text-sm text-[var(--color-text-main)] shadow-2xs focus:outline-none focus:ring-2 transition ' . 
                ($hasError 
                    ? 'border-[var(--color-danger)] focus:ring-[var(--color-danger)] focus:border-[var(--color-danger)]' 
                    : 'border-[var(--color-surface-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]')
            ]) }}
        >
            @if($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif

            @foreach($options as $val => $optLabel)
                <option value="{{ $val }}" {{ (string)$selectedValue === (string)$val ? 'selected' : '' }}>
                    {{ $optLabel }}
                </option>
            @endforeach

            {{ $slot }}
        </select>
    </div>

    @if($helpText && !$hasError)
        <p class="mt-1 text-[11px] text-[var(--color-text-muted)]">{{ $helpText }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-xs text-[var(--color-danger)] font-medium">{{ $message }}</p>
    @enderror
</div>
