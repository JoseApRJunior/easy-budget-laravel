{{-- Flash Messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mx-4 mt-4" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            <i class="bi bi-x"></i>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mx-4 mt-4" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            <i class="bi bi-x"></i>
        </button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show mx-4 mt-4" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            <i class="bi bi-x"></i>
        </button>
    </div>
@endif

@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show mx-4 mt-4" role="alert">
        <i class="bi bi-info-circle me-2"></i>
        {{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            <i class="bi bi-x"></i>
        </button>
    </div>
@endif

{{-- Validation Errors --}}
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mx-4 mt-4" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Ops! Alguns erros foram encontrados:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            <i class="bi bi-x"></i>
        </button>
    </div>
@endif

{{-- Custom Alert Component --}}
<div id="custom-alerts-container" class="mx-4 mt-4"></div>

<script>
// Função para criar alertas dinâmicos (compatibilidade com sistema legado)
function showAlert(message, type = 'info', duration = 5000) {
    const container = document.getElementById('custom-alerts-container');
    const alertId = 'alert-' + Date.now();
    
    const iconMap = {
        'success': 'bi-check-circle',
        'error': 'bi-exclamation-triangle',
        'danger': 'bi-exclamation-triangle',
        'warning': 'bi-exclamation-triangle',
        'info': 'bi-info-circle'
    };
    
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="bi ${iconMap[type] || 'bi-info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" onclick="closeAlert('${alertId}')" aria-label="Close">
                <i class="bi bi-x"></i>
            </button>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', alertHtml);
    
    // Auto-remove after duration
    if (duration > 0) {
        setTimeout(() => {
            closeAlert(alertId);
        }, duration);
    }
}

function closeAlert(alertId) {
    const alert = document.getElementById(alertId);
    if (alert) {
        alert.classList.remove('show');
        alert.classList.add('fade');
        setTimeout(() => {
            alert.remove();
        }, 150);
    }
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(#custom-alerts-container .alert)');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.classList.contains('show')) {
                const closeBtn = alert.querySelector('.btn-close');
                if (closeBtn) {
                    closeBtn.click();
                }
            }
        }, 5000);
    });
});

// Compatibilidade com sistema legado
window.Alert = {
    success: (message, duration) => showAlert(message, 'success', duration),
    error: (message, duration) => showAlert(message, 'danger', duration),
    warning: (message, duration) => showAlert(message, 'warning', duration),
    info: (message, duration) => showAlert(message, 'info', duration)
};
</script>

<style>
.alert-dismissible .btn-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    opacity: 0.5;
    transition: opacity 0.15s ease-in-out;
}

.alert-dismissible .btn-close:hover {
    opacity: 0.75;
}

.alert.fade {
    transition: opacity 0.15s linear;
}

.alert.show {
    opacity: 1;
}

.alert:not(.show) {
    opacity: 0;
}
</style>