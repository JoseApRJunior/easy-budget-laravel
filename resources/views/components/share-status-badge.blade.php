@props(['share'])

@php
$isExpired = !$share->is_active || 
    ($share->expires_at && $share->expires_at <= now());

if ($isExpired) {
    $badgeClass = 'bg-danger-subtle text-danger';
    $icon = 'bi-x-circle';
    $text = 'Expirado';
} else {
    $badgeClass = 'bg-success-subtle text-success';
    $icon = 'bi-check-circle';
    $text = 'Ativo';
}

// Check if expiring soon (within 3 days)
if (!$isExpired && $share->expires_at && $share->expires_at <= now()->addDays(3)) {
    $badgeClass = 'bg-warning-subtle text-warning';
    $icon = 'bi-exclamation-triangle';
}
@endphp

<span class="badge {{ $badgeClass }}">
    <i class="bi {{ $icon }} me-1"></i>
    {{ $text }}
</span>

@if($share->expires_at && !$isExpired)
    <small class="text-muted ms-2">
        Expira em {{ $share->expires_at->diffForHumans() }}
    </small>
@endif