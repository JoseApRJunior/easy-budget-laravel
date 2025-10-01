{{-- Componente Modal Avançado - Fase 2 --}}

@props([
    'id' => 'modal',
    'title' => 'Modal',
    'size' => 'md', // sm, md, lg, xl, full
    'closeable' => true,
    'footer' => true
])

@php
    $sizeClasses = [
        'sm' => 'max-w-md',
        'md' => 'max-w-lg',
        'lg' => 'max-w-4xl',
        'xl' => 'max-w-6xl',
        'full' => 'max-w-full mx-4'
    ];
@endphp

<!-- Modal Backdrop -->
<div id="{{ $id }}" class="fixed inset-0 z-50 overflow-y-auto hidden" x-data="modalComponent()" x-show="open" x-transition>
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeModal()"></div>

    <!-- Modal Container -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full {{ $sizeClasses[$size] }} bg-white rounded-lg shadow-xl transform transition-all"
             x-show="open" x-transition
             @click.away="closeModal()"
             x-trap.inert.noscroll="open">

            <!-- Modal Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                @if($closeable)
                    <button type="button" @click="closeModal()"
                            class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                @endif
            </div>

            <!-- Modal Body -->
            <div class="p-4 max-h-96 overflow-y-auto">
                {{ $slot }}
            </div>

            <!-- Modal Footer -->
            @if($footer)
                <div class="flex items-center justify-end gap-3 p-4 border-t border-gray-200 bg-gray-50">
                    <button type="button" @click="closeModal()" class="btn btn-secondary">
                        <i class="bi bi-x-lg mr-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary">
                        <i class="bi bi-check-lg mr-2"></i>Confirmar
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function modalComponent() {
    return {
        open: false,

        show() {
            this.open = true;
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.open = false;
            document.body.style.overflow = '';
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
    }
}

// Função global para abrir modal
window.openModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal && modal._x_dataStack) {
        modal._x_dataStack[0].show();
    }
}

// Função global para fechar modal
window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal && modal._x_dataStack) {
        modal._x_dataStack[0].closeModal();
    }
}
</script>
@endpush
