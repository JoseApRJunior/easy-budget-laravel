@props([
    'icon' => null,
    'avatarColor' => 'var(--primary-color)',
    'avatarTextColor' => 'white',
    'href' => null
])

@if($href)
    <a href="{{ $href }}" class="list-group-item list-group-item-action py-3 border-0 mb-3 rounded shadow-sm hover-bg">
@else
    <div class="list-group-item py-3 border-0 mb-3 rounded shadow-sm hover-bg">
@endif
    <div class="d-flex align-items-start w-100">
        @if(isset($avatar) || $icon)
            <div class="me-3 mt-1 flex-shrink-0">
                @if(isset($avatar))
                    {{ $avatar }}
                @else
                    <div class="avatar-circle"
                        style="width: 40px; height: 40px; background-color: {{ $avatarColor }}; color: {{ $avatarTextColor }}; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-{{ $icon }}"></i>
                    </div>
                @endif
            </div>
        @endif

        <div class="flex-grow-1" style="min-width: 0;">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="flex-grow-1">
                    {{ $slot }}
                </div>

                @if(isset($topActions))
                    <div class="ms-2">
                        {{ $topActions }}
                    </div>
                @endif
            </div>

            @if(isset($description))
                <div class="mb-2">
                    {{ $description }}
                </div>
            @endif

            @if(isset($footer) || isset($actions))
                <div class="d-flex justify-content-between align-items-end mt-3 pt-2">
                    <div class="flex-grow-1">
                        @if(isset($footer))
                            {{ $footer }}
                        @endif
                    </div>

                    @if(isset($actions))
                        <div class="ms-2 flex-shrink-0">
                            {{ $actions }}
                        </div>
                    @endif
                </div>
            @endif
        </div>

        @if($href)
            <i class="bi bi-chevron-right text-muted ms-2 mt-1"></i>
        @endif
    </div>
@if($href)
    </a>
@else
    </div>
@endif
