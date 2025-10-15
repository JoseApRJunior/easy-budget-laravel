@props( [ 'slot' ] )

{{-- Componente message para e-mails - baseado no componente padrão do Laravel --}}
<div {{$attributes->merge( [ 'class' => 'max-w-2xl mx-auto bg-white rounded-lg shadow-sm' ] )}}>
    <div class="px-6 py-4">
        {{$slot}}
    </div>
</div>
