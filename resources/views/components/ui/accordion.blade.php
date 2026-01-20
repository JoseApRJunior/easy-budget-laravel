@props([
    'id' => 'accordion-' . uniqid(),
    'flush' => false,
    'items' => [], // Array de ['title' => '', 'content' => '', 'active' => false]
])

<div {{ $attributes->merge(['class' => 'accordion' . ($flush ? ' accordion-flush' : '')]) }} id="{{ $id }}">
    @if(count($items) > 0)
        @foreach($items as $index => $item)
            @php
                $itemId = $id . '-item-' . $index;
                $active = $item['active'] ?? false;
            @endphp
            <div class="accordion-item border-0 @if(!$loop->last) border-bottom @endif">
                <h2 class="accordion-header">
                    <button class="accordion-button @if(!$active) collapsed @endif fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $itemId }}">
                        {{ $item['title'] }}
                    </button>
                </h2>
                <div id="{{ $itemId }}" class="accordion-collapse collapse @if($active) show @endif" data-bs-parent="#{{ $id }}">
                    <div class="accordion-body text-muted">
                        {!! $item['content'] !!}
                    </div>
                </div>
            </div>
        @endforeach
    @else
        {{ $slot }}
    @endif
</div>
