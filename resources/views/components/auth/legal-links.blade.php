@props([
    'mode' => 'full', // 'full' para a frase completa, 'links' apenas para os links
])

@if($mode === 'full')
    Ao continuar, você concorda com nossos
    <a href="{{ route('terms') }}" target="_blank" class="fw-bold text-primary text-decoration-none">Termos de Serviço</a>
    e
    <a href="{{ route('privacy') }}" target="_blank" class="fw-bold text-primary text-decoration-none">Política de Privacidade</a>.
@else
    <a href="{{ route('terms') }}" target="_blank" class="fw-bold text-primary text-decoration-none">Termos de Serviço</a> 
    e a 
    <a href="{{ route('privacy') }}" target="_blank" class="fw-bold text-primary text-decoration-none">Política de Privacidade</a>.
@endif
