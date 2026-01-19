<x-app-layout title="Verificar E-mail">
    <x-auth.split-layout
        title="Verifique seu E-mail"
        subtitle="Enviamos um link de confirmação para o endereço de e-mail fornecido durante o cadastro.">

        <x-slot:welcome>
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

            <x-auth.security-note>
                {{ __('Caso não encontre o e-mail, use o botão ao lado para reenviar.') }}
            </x-auth.security-note>
        </x-slot:welcome>

        <x-ui.auth-session-status :status="session('status') == 'verification-link-sent' ? __('Um novo link de verificação foi enviado para o seu e-mail.') : null" />

        <x-ui.form.form :action="route('verification.send')">
            <x-ui.form.actions class="mb-3">
                <x-ui.button type="submit" variant="primary" size="lg" icon="envelope-arrow-up" label="Reenviar E-mail de Verificação" class="w-100" />
            </x-ui.form.actions>
        </x-ui.form.form>

        <form method="POST" action="{{ route('logout') }}" class="w-100">
            @csrf
            <x-ui.form.actions>
                <x-ui.button type="submit" variant="outline-secondary" size="lg" icon="box-arrow-right" label="Sair do Sistema" class="w-100" />
            </x-ui.form.actions>
        </form>

        <x-auth.footer
            text="Precisa de ajuda?"
            linkText="Falar com Suporte"
            :linkHref="route('support')" />
    </x-auth.split-layout>
</x-app-layout>

@push( 'styles' )
@endpush
