@props(['headers' => null])

<div class="table-responsive">
    <table {{ $attributes->merge(['class' => 'modern-table table mb-0']) }}>
        @if(isset($thead) || !empty($headers))
            <thead>
                @if(isset($thead))
                    {{ $thead }}
                @elseif(!empty($headers))
                    <x-resource.table-row>
                        @foreach($headers as $header)
                            <x-resource.table-cell header>{{ $header }}</x-resource.table-cell>
                        @endforeach
                    </x-resource.table-row>
                @endif
            </thead>
        @endif

        @if(isset($tbody))
            <tbody>
                {{ $tbody }}
            </tbody>
        @else
            <tbody>
                {{ $slot }}
            </tbody>
        @endif

        @if(isset($tfoot))
            <tfoot>
                {{ $tfoot }}
            </tfoot>
        @endif
    </table>
</div>
