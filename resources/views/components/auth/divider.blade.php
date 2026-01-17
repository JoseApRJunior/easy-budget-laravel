@props([
    'label' => 'ou',
])

<div class="position-relative text-center my-4 py-2">
    <div class="position-absolute top-50 start-0 end-0 border-top opacity-10"></div>
    <span class="position-relative bg-white px-3 small text-muted text-uppercase fw-semibold" 
          style="letter-spacing: 1.5px; font-size: 0.65rem;">
        {{ $label }}
    </span>
</div>
