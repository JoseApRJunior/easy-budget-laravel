@props([
    'mobile' => false
])

@if($mobile)
    <div {{ $attributes->merge(['class' => 'd-flex gap-1']) }}>
        {{ $slot }}
    </div>
@else
    <td {{ $attributes->merge(['class' => 'text-center']) }}>
        <div class="action-btn-group">
            {{ $slot }}
        </div>
    </td>
@endif
