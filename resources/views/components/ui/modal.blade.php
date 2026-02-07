@props([
'id',
'title',
'icon' => null,
'size' => '', // modal-sm, modal-lg, modal-xl
'centered' => true,
'scrollable' => false,
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog {{ $size }} {{ $centered ? 'modal-dialog-centered' : '' }} {{ $scrollable ? 'modal-dialog-scrollable' : '' }}">
        <div class="modal-content">
            <div class="modal-header border-bottom py-3" style="border-color: {{ config('theme.colors.form_border', '#b4c2d3') }} !important;">
                <h5 class="modal-title d-flex align-items-center" id="{{ $id }}Label">
                    @if($icon)
                    <i class="bi bi-{{ $icon }} me-2 text-primary"></i>
                    @endif
                    {{ $title }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                {{ $slot }}
            </div>
            @if(isset($footer))
            <div class="modal-footer">
                {{ $footer }}
            </div>
            @endif
        </div>
    </div>
</div>
