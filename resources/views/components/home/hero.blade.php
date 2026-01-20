@props([
    'title' => 'Bem-vindo ao Easy Budget',
    'description' => 'Transforme a gestão de seus serviços com nossas soluções diversificadas e inovadoras.',
    'buttonText' => 'Conheça nossos planos',
    'buttonTarget' => 'plans',
    'secondaryButtonText' => 'Ver Recursos',
    'secondaryButtonTarget' => 'features'
])

<section id="home" {{ $attributes->merge(['class' => 'hero-section text-center position-relative mt-5']) }}>
    <div class="hero-overlay"></div>
    <div class="main-container position-relative">
        {{ $slot }}

        <div class="hero-content">
            <h1 class="display-4 fw-bold mb-3">{{ $title }}</h1>
            <p class="lead mb-4">{{ $description }}</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <x-ui.button
                    id="conhecaPlanos"
                    variant="primary"
                    size="lg"
                    class="pulse-button"
                    icon="arrow-down-circle"
                    :label="$buttonText"
                    onclick="document.getElementById('{{ $buttonTarget }}').scrollIntoView({behavior: 'smooth'})"
                />
                <x-ui.button
                    variant="outline-primary"
                    size="lg"
                    icon="grid"
                    :label="$secondaryButtonText"
                    onclick="document.getElementById('{{ $secondaryButtonTarget }}').scrollIntoView({behavior: 'smooth'})"
                />
            </div>
        </div>
    </div>
</section>
