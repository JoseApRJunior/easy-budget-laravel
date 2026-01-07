<div class="table-responsive">
    <table {{ $attributes->merge(['class' => 'modern-table table mb-0']) }}>
        @if(isset($thead))
            <thead>
                {{ $thead }}
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
