@if ($errors->any())
    <x-ui.alert type="error" class="mb-4">
        <x-slot:message>
            <div class="fw-bold mb-1">Ops! Verifique os erros abaixo:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-slot:message>
    </x-ui.alert>
@endif
