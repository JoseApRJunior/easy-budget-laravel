<div class="card-footer bg-transparent border-0">
    <div class="d-flex justify-content-end gap-2">
        @php
            $provider = auth()->user()->provider;
            $isCompany = false;
            if ($provider?->commonData) {
                $isCompany = $provider->commonData->cnpj || $provider->commonData->type === 'company';
            }
        @endphp
        
        @if($isCompany)
            {{-- Prioriza botão empresarial para PJ --}}
            <a href="{{ route( 'provider.business.edit' ) }}" class="btn btn-primary">
                <i class="bi bi-building me-2"></i>Editar Dados Empresariais
            </a>
            <a href="{{ route( 'settings.profile.edit' ) }}" class="btn btn-outline-primary">
                <i class="bi bi-person me-2"></i>Editar Perfil Pessoal
            </a>
        @else
            {{-- Ordem padrão para PF --}}
            <a href="{{ route( 'settings.profile.edit' ) }}" class="btn btn-primary">
                <i class="bi bi-person me-2"></i>Editar Perfil Pessoal
            </a>
            <a href="{{ route( 'provider.business.edit' ) }}" class="btn btn-outline-info">
                <i class="bi bi-building me-2"></i>Editar Dados Empresariais
            </a>
        @endif
        
        <a href="{{ route('settings.index') }}?tab=seguranca" class="btn btn-outline-secondary">
            <i class="bi bi-shield-lock me-2"></i>Configurações de Segurança
        </a>
    </div>
</div>
