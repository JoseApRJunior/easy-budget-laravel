@props([
    'name',
    'label' => null,
    'id' => null,
    'checked' => false,
    'required' => false,
    'switch' => false,
])

@php
    $id = $id ?? $name;
    $wrapperClasses = 'form-check mb-4';
    if ($switch) {
        $wrapperClasses .= ' form-switch';
    }
@endphp

<div class="{{ $wrapperClasses }}">
    <input 
        class="form-check-input @error($name) is-invalid @enderror" 
        type="checkbox" 
        id="{{ $id }}" 
        name="{{ $name }}" 
        value="1" 
        @if($checked || old($name)) checked @endif
        @if($required) required @endif
        {{ $attributes }}
    >
    <label class="form-check-label small text-muted" for="{{ $id }}">
        {{ $label ?? $slot }}
    </label>
    @error($name)
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>
