@props([
    'type' => 'info',
    'message' => '',
    'dismissible' => true,
    'icon' => true,
    'autoHide' => true,
    'duration' => 5000
])

@php
    $typeClasses = [
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'danger' => 'bg-red-50 border-red-200 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800',
    ];

    $icons = [
        'success' => 'bi-check-circle-fill',
        'error' => 'bi-exclamation-circle-fill',
        'danger' => 'bi-exclamation-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'info' => 'bi-info-circle-fill',
    ];

    $baseClasses = 'border rounded-lg p-4 flex items-start space-x-3';
    $classes = collect([$baseClasses, $typeClasses[$type] ?? $typeClasses['info']])->implode(' ');
@endphp

<div
    x-data="alertComponent({
        autoHide: {{ $autoHide ? 'true' : 'false' }},
        duration: {{ $duration }}
    })"
    x-show="visible"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-90"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-90"
    class="{{ $classes }}"
    role="alert"
    aria-live="polite"
>
    <!-- Ícone -->
    @if($icon)
        <div class="flex-shrink-0">
            <i class="{{ $icons[$type] ?? $icons['info'] }} text-xl"></i>
        </div>
    @endif

    <!-- Conteúdo -->
    <div class="flex-1 min-w-0">
        @if($message)
            {!! $message !!}
        @else
            {{ $slot }}
        @endif
    </div>

    <!-- Botão Dismiss -->
    @if($dismissible)
        <div class="flex-shrink-0">
            <button
                @click="dismiss()"
                type="button"
                class="text-current opacity-50 hover:opacity-100 transition-opacity focus:outline-none focus:ring-2 focus:ring-current focus:ring-offset-2 rounded"
                aria-label="Fechar alerta"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    @endif
</div>

<script>
function alertComponent(config = {}) {
    return {
        visible: true,
        autoHide: config.autoHide ?? true,
        duration: config.duration ?? 5000,

        init() {
            if (this.autoHide) {
                setTimeout(() => {
                    this.dismiss();
                }, this.duration);
            }
        },

        dismiss() {
            this.visible = false;
        }
    }
}
</script>
