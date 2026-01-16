@props([
    'label' => 'ou',
])

<div class="position-relative text-center my-4">
    {{-- Linha horizontal --}}
    <div class="position-absolute top-50 start-0 end-0 border-top opacity-25"></div>

    {{-- Label com efeito de caixa/pill --}}
    <span class="position-relative bg-white px-3 py-1 small text-muted fw-bold text-uppercase border rounded shadow-sm"
          style="letter-spacing: 1px; font-size: 0.7rem;">
        {{ $label }}
    </span>
</div>
