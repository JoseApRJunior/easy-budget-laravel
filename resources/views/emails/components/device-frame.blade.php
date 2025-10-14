@props([
    'device' => 'desktop',
    'content' => '',
    'metadata' => []
])

@php
    $deviceConfigs = [
        'desktop' => [
            'width' => 1200,
            'height' => 800,
            'class' => 'desktop',
            'notch' => false,
        ],
        'tablet' => [
            'width' => 768,
            'height' => 1024,
            'class' => 'tablet',
            'notch' => false,
        ],
        'mobile' => [
            'width' => 375,
            'height' => 667,
            'class' => 'mobile',
            'notch' => true,
        ],
    ];

    $config = $deviceConfigs[$device] ?? $deviceConfigs['desktop'];
    $renderTime = $metadata['render_time_ms'] ?? 0;
    $htmlSize = $metadata['html_size_bytes'] ?? 0;
@endphp

<div class="device-frame {{ $config['class'] }}" style="width: {{ $config['width'] }}px; height: {{ $config['height'] }}px;">
    @if($config['notch'])
    <div class="device-notch"></div>
    @endif

    <div class="device-screen">
        <iframe
            srcdoc="{{ $content }}"
            width="100%"
            height="100%"
            frameborder="0"
            sandbox="allow-same-origin"
            loading="lazy">
        </iframe>
    </div>

    <!-- Indicadores de Performance -->
    @if($renderTime > 0)
    <div class="device-performance-indicators">
        <div class="performance-badge render-time" title="Tempo de renderização">
            <i class="fas fa-clock"></i>
            {{ number_format($renderTime, 1) }}ms
        </div>
        @if($htmlSize > 0)
        <div class="performance-badge html-size" title="Tamanho do HTML">
            <i class="fas fa-file-code"></i>
            {{ number_format($htmlSize / 1024, 1) }}KB
        </div>
        @endif
    </div>
    @endif
</div>

<style>
.device-frame {
    border: 8px solid #333;
    border-radius: 20px;
    overflow: hidden;
    margin: 2rem auto;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    position: relative;
    background: #000;
    transition: all 0.3s ease;
}

.device-frame:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
}

.device-frame.mobile {
    border-radius: 25px;
}

.device-frame.tablet {
    border-radius: 15px;
}

.device-screen {
    width: 100%;
    height: 100%;
    background: #fff;
    position: relative;
    overflow: hidden;
}

.device-screen iframe {
    width: 100%;
    height: 100%;
    border: none;
    background: #fff;
}

.device-notch {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 120px;
    height: 20px;
    background: #333;
    border-radius: 0 0 15px 15px;
    z-index: 10;
}

.device-performance-indicators {
    position: absolute;
    bottom: 10px;
    right: 10px;
    display: flex;
    gap: 0.5rem;
    z-index: 20;
}

.performance-badge {
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.performance-badge.render-time {
    background: rgba(102, 126, 234, 0.9);
}

.performance-badge.html-size {
    background: rgba(40, 167, 69, 0.9);
}

.device-frame.mobile .device-screen {
    padding-top: 20px; /* Espaço para o notch */
}

.device-frame.desktop {
    border: 2px solid #ddd;
    background: #f5f5f5;
}

.device-frame.desktop .device-screen {
    border: 1px solid #ccc;
}

/* Responsividade */
@media (max-width: 768px) {
    .device-frame.mobile,
    .device-frame.tablet {
        width: 100%;
        max-width: 375px;
        height: auto;
        aspect-ratio: 9/16;
    }

    .device-frame.desktop {
        width: 100%;
        height: 600px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Adicionar indicador de carregamento
    const iframe = document.querySelector('.device-screen iframe');

    if (iframe) {
        iframe.addEventListener('load', function() {
            // Remover indicador de carregamento se existir
            const loadingIndicator = iframe.parentElement.querySelector('.loading-indicator');
            if (loadingIndicator) {
                loadingIndicator.remove();
            }
        });

        // Adicionar indicador de erro
        iframe.addEventListener('error', function() {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger m-3';
            errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Erro ao carregar preview do e-mail';
            iframe.parentElement.appendChild(errorDiv);
        });
    }
});
</script>
