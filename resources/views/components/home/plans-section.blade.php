@props([
    'plans',
    'availableResources' => [],
    'title' => 'Escolha o Plano Perfeito para Você',
    'subtitle' => 'Selecione o plano que melhor atende às suas necessidades'
])

<section id="plans" {{ $attributes->merge(['class' => 'py-5']) }} aria-labelledby="plans-title">
    <div class="main-container">
        <div class="section-header text-center mb-5">
            <h2 id="plans-title" class="display-6 fw-bold">{{ $title }}</h2>
            <p class="small-text">{{ $subtitle }}</p>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4 mb-5">
            @foreach( $plans as $plan )
                <x-home.plan-card :plan="$plan" :isPopular="$plan['slug'] == 'pro'" :availableResources="$availableResources" />
            @endforeach
        </div>

        {{ $slot }}
    </div>
</section>
