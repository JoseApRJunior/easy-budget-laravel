@props( [ 'type', 'message' ] )

@php
    $flashTypes = [
        'error'   => 'danger',
        'success' => 'success',
        'message' => 'info',
        'warning' => 'warning',
    ];
    $alertClass = $flashTypes[ $type ] ?? 'info';
@endphp

<div {{ $attributes->merge( [ 'class' => 'alert alert-' . $alertClass . ' alert-dismissible fade show text-center' ] ) }}
    role="alert">
    {!! $message !!}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
