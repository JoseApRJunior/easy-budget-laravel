<x-app-layout title="Verificar E-mail">
    <x-auth.split-layout
        :title="($status ?? '') === 'inactive' ? 'Conta Inativa' : 'Verifique seu E-mail'"
        :subtitle="$message ?? 'Enviamos um link de confirmação para o endereço de e-mail fornecido durante o cadastro.'">

        <x-slot:welcome>
            @if(($status ?? '') === 'inactive')
                <x-auth.welcome-header
                    title="Aguardando Ativação"
                    subtitle="Seu e-mail já foi verificado, mas sua conta ainda não está totalmente ativa."
                />

                <x-auth.feature-grid>
                    <x-auth.feature-item label="Sua conta está em processo de revisão" />
                    <x-auth.feature-item icon="shield-lock" label="Segurança dos seus dados garantida" />
                    <x-auth.feature-item icon="chat-dots" label="Suporte pronto para te ajudar" />
                    <x-auth.feature-item icon="check2-all" label="Acesso total liberado após ativação" />
                </x-auth.feature-grid>
            @else
                <x-auth.welcome-header
                    title="Quase lá!"
                    subtitle="Verifique sua caixa de entrada para ativar sua conta e começar a usar o Easy Budget."
                />

                <x-auth.feature-grid>
                    <x-auth.feature-item label="Ative todos os recursos do sistema" />
                    <x-auth.feature-item icon="inboxes" label="Verifique sua caixa de entrada e spam" />
                    <x-auth.feature-item icon="link-45deg" label="Clique no link de confirmação no e-mail" />
                    <x-auth.feature-item icon="shield-check" label="Tenha acesso total ao seu painel" />
                </x-auth.feature-grid>
            @endif

            <x-auth.security-note>
                {{ ($status ?? '') === 'inactive'
                    ? 'Se você acredita que isso é um erro, entre em contato com nosso suporte.'
                    : 'Caso não encontre o e-mail, use o botão ao lado para reenviar.' }}
            </x-auth.security-note>
        </x-slot:welcome>

        @if(($status ?? '') !== 'inactive')
            <x-ui.form.form :action="route('verification.send')">
                <x-ui.form.actions class="mb-3">
                    <x-ui.button type="submit" variant="primary" size="lg" icon="envelope-arrow-up" label="Reenviar E-mail de Verificação" class="w-100" />
                </x-ui.form.actions>
            </x-ui.form.form>
        @endif

        <x-ui.form.form :action="route('logout')" class="w-100">
            <x-ui.form.actions>
                <x-ui.button type="submit" variant="outline-secondary" size="lg" icon="box-arrow-right" label="Sair do Sistema" class="w-100" />
            </x-ui.form.actions>
        </x-ui.form.form>

        <x-auth.footer
            text="Precisa de ajuda?"
            linkText="Falar com Suporte"
            :linkHref="route('support')" />
    </x-auth.split-layout>
</x-app-layout>

@push( 'styles' )
@endpush
