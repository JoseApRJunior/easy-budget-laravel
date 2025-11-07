<div class="col-md-8">
    <div class="row g-3">
        @php
            $provider = auth()->user()->provider;
            $isCompany = false;
            if ($provider?->commonData) {
                $isCompany = $provider->commonData->cnpj || $provider->commonData->type === 'company';
            }
        @endphp
        
        @if($isCompany)
            {{-- Prioriza informações da empresa para PJ --}}
            @include( 'partials.settings.profile.company_info' )
            @include( 'partials.settings.profile.personal_info' )
        @else
            {{-- Ordem padrão para PF --}}
            @include( 'partials.settings.profile.personal_info' )
            @include( 'partials.settings.profile.company_info' )
        @endif
        
        @include( 'partials.settings.profile.membership_info' )
    </div>
</div>
