@props([
    'name',
    'label' => 'Telefone',
    'id' => null,
    'value' => '',
    'placeholder' => '(11) 99999-9999',
    'required' => false,
    'icon' => 'telephone',
])

@php
    $id = $id ?? $name;
@endphp

<x-ui.form.input-group 
    :name="$name" 
    :id="$id" 
    :label="$label" 
    :value="$value" 
    :placeholder="$placeholder" 
    :required="$required" 
    :icon="$icon"
    type="tel"
    {{ $attributes->merge(['class' => 'phone-mask']) }}
/>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const phoneInputs = document.querySelectorAll('.phone-mask');
                phoneInputs.forEach(input => {
                    input.addEventListener('input', function(e) {
                        let v = e.target.value.replace(/\D/g, '');
                        if(v.length > 11) v = v.substring(0, 11);
                        if(v.length > 10) {
                            v = v.replace(/^(\d\d)(\d{5})(\d{4}).*/, '($1) $2-$3');
                        } else if(v.length > 5) {
                            v = v.replace(/^(\d\d)(\d{4})(\d{0,4}).*/, '($1) $2-$3');
                        } else if(v.length > 2) {
                            v = v.replace(/^(\d\d)(\d{0,5}).*/, '($1) $2');
                        }
                        e.target.value = v;
                    });
                });
            });
        </script>
    @endpush
@endonce
