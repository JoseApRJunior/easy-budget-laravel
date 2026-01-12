@props([
    'title',
    'value',
    'description' => null,
    'icon' => null,
    'variant' => 'primary', // primary, success, info, warning, danger, secondary
    'gradient' => true,
    'isCustom' => false, // Para col-xl-5-custom
    'col' => null // Permite sobrescrever as classes de coluna
])

@php
    // Define se o texto deve ser escuro ou claro baseado na variante quando em modo gradient
    // Para melhor legibilidade, usaremos tons escuros na maioria dos casos,
    // exceto talvez em variantes muito escuras.
    $isDarkVariant = in_array($variant, ['primary', 'success', 'danger', 'dark', 'secondary']);
    $textColor = 'dark'; // Padrão agora é escuro conforme feedback

    // Se for uma variante escura e tiver gradient, podemos usar um branco com opacidade alta
    // ou manter o preto dependendo do contraste desejado.
    // O usuário mencionou que o branco fica estranho e prefere as escritas mais escuras.
@endphp

<div class="{{ $col ?? ($isCustom ? 'col-12 col-md-6 col-xl-5-custom' : 'col-12 col-md-6 col-lg-3') }}">
    <div @class([
        'card border-0 shadow-sm h-100',
        "bg-$variant bg-gradient" => $gradient,
        "text-dark" => $gradient, // Forçamos texto escuro para melhor contraste sobre as cores do gradient
    ])>
        <div class="card-body p-3 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                @if($icon)
                    <div @class([
                        'avatar-circle me-2',
                        'bg-black bg-opacity-10' => $gradient,
                        "bg-$variant bg-gradient" => !$gradient
                    ]) style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                        <i @class([
                            'bi bi-' . $icon,
                            'text-dark' => $gradient,
                            "text-$variant" => !$gradient
                        ]) style="font-size: 0.9rem;"></i>
                    </div>
                @endif
                <h6 @class([
                    'mb-0 small fw-bold text-uppercase',
                    'text-dark text-opacity-80' => $gradient,
                    'text-muted' => !$gradient
                ])>{{ $title }}</h6>
            </div>
            <h3 @class([
                'mb-1 fw-bold',
                'text-dark' => $gradient,
                "text-$variant" => !$gradient
            ])>{{ $value }}</h3>
            @if($description)
                <p @class([
                    'small-text mb-0',
                    'text-dark text-opacity-80' => $gradient,
                    'text-muted' => !$gradient
                ]) style="font-size: 0.75rem; font-weight: 500;">{{ $description }}</p>
            @endif
        </div>
    </div>
</div>
