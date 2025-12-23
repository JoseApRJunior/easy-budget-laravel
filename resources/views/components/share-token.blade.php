@props(['token', 'label' => 'Token de Acesso', 'showCopy' => true])

<div class="input-group mb-3">
    <span class="input-group-text">
        <i class="bi bi-key"></i>
    </span>
    <input 
        type="text" 
        class="form-control" 
        value="{{ $token }}" 
        readonly 
        id="share-token-{{ uniqid() }}"
        aria-label="{{ $label }}"
    >
    @if($showCopy)
        <button 
            class="btn btn-outline-secondary" 
            type="button" 
            onclick="copyShareToken(this)"
            data-token="{{ $token }}"
            title="Copiar token"
        >
            <i class="bi bi-clipboard"></i>
        </button>
    @endif
</div>

@push('scripts')
<script>
function copyShareToken(button) {
    const token = button.getAttribute('data-token');
    const originalIcon = button.innerHTML;
    
    navigator.clipboard.writeText(token).then(function() {
        // Change icon to show success
        button.innerHTML = '<i class="bi bi-check"></i>';
        button.classList.remove('btn-outline-secondary');
        button.classList.add('btn-success');
        button.title = 'Token copiado!';
        
        // Reset after 2 seconds
        setTimeout(function() {
            button.innerHTML = originalIcon;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-secondary');
            button.title = 'Copiar token';
        }, 2000);
    }).catch(function(err) {
        console.error('Erro ao copiar token:', err);
        alert('Erro ao copiar token. Por favor, copie manualmente.');
    });
}
</script>
@endpush