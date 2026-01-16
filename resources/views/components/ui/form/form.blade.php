@props([
    'action',
    'method' => 'POST',
    'id' => null,
])

@php
    $method = strtoupper($method);
    $actualMethod = in_array($method, ['GET', 'POST']) ? $method : 'POST';
    $spoofedMethod = in_array($method, ['PUT', 'PATCH', 'DELETE']) ? $method : null;
@endphp

<form action="{{ $action }}" method="{{ $actualMethod }}" @if($id) id="{{ $id }}" @endif {{ $attributes }}>
    @if($actualMethod !== 'GET')
        @csrf
    @endif

    @if($spoofedMethod)
        @method($spoofedMethod)
    @endif

    {{ $slot }}
</form>
