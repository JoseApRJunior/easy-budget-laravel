{{--
Aba de configurações gerais
Uso: @include('settings.general')
--}}

<div class="tab-pane fade" id="geral">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0">
            <h3 class="h5 mb-0">Configurações Gerais</h3>
        </div>
        <div class="card-body">
            @include( 'settings.forms.general-form' )
        </div>
    </div>
</div>
