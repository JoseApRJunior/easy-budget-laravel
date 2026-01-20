@props([
    'title' => 'Pronto para começar?',
    'description' => 'Junte-se a milhares de profissionais que já estão transformando seus negócios com o Easy Budget.',
    'primaryButtonText' => 'Começar Agora',
    'primaryButtonRoute' => 'register',
    'secondaryButtonText' => 'Saiba Mais',
    'secondaryButtonTarget' => 'plans'
])

<div class="row justify-content-center mt-5">
    <div class="col-lg-8 text-center">
        <div {{ $attributes->merge(['class' => 'cta-section card h-100 shadow-sm hover-card rounded-3 p-5']) }}>
            <h3 class="mb-3">{{ $title }}</h3>
            <p class="lead mb-4">{{ $description }}</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <x-ui.button :href="route($primaryButtonRoute)" variant="light" size="lg" icon="person-plus" :label="$primaryButtonText" />
                <x-ui.button
                    variant="light"
                    :outline="true"
                    size="lg"
                    icon="info-circle"
                    :label="$secondaryButtonText"
                    onclick="document.getElementById('{{ $secondaryButtonTarget }}').scrollIntoView({behavior: 'smooth'})"
                />
            </div>
            <div class="mt-4">
                <small class="opacity-75">
                    <i class="bi bi-shield-check me-1"></i>
                    Cadastro gratuito • Sem taxas ocultas • Cancele quando quiser
                </small>
            </div>
        </div>
    </div>
</div>
