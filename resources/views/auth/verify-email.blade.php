<x-app-layout :title="$title ?? 'Verificar E-mail'">
    <x-auth.split-layout
        :title="$title ?? 'Verifique seu E-mail'"
        :subtitle="$message ?? 'Enviamos um link de confirmação para o endereço de e-mail fornecido durante o cadastro.'">

        <x-slot:welcome>
            @if(($status ?? '') === 'deactivated')
                <x-auth.welcome-header
                    title="Conta Desativada"
                    subtitle="Seu acesso ao sistema foi suspenso temporariamente."
                />

                <x-auth.feature-grid>
                    <x-auth.feature-item icon="shield-lock" label="Sua conta está em processo de revisão" />
                    <x-auth.feature-item icon="chat-dots" label="O suporte pode ajudar na reativação" />
                    <x-auth.feature-item icon="file-lock2" label="Seus dados continuam seguros conosco" />
                    <x-auth.feature-item icon="check2-all" label="Acesso total liberado após reativação" />
                </x-auth.feature-grid>

                <x-auth.security-note>
                    Se você acredita que isso é um erro ou deseja reativar sua conta, entre em contato com nosso suporte.
                </x-auth.security-note>
            @else
                <x-auth.welcome-header
                    title="Ative sua Conta"
                    subtitle="Verifique sua caixa de entrada para começar a usar o Easy Budget."
                />

                <x-auth.feature-grid>
                    <x-auth.feature-item label="Ative todos os recursos do sistema" />
                    <x-auth.feature-item icon="inboxes" label="Verifique sua caixa de entrada e spam" />
                    <x-auth.feature-item icon="link-45deg" label="Clique no link de confirmação no e-mail" />
                    <x-auth.feature-item icon="shield-check" label="Tenha acesso total ao seu painel" />
                </x-auth.feature-grid>

                <x-auth.security-note>
                    Caso não encontre o e-mail, use o botão ao lado para reenviar o link de ativação.
                </x-auth.security-note>
            @endif
        </x-slot:welcome>

        @if(session('status'))
            <x-ui.alert variant="success" :message="session('status')" class="mb-4" />
        @endif

        @if(session('success'))
            <x-ui.alert variant="success" :message="session('success')" class="mb-4" />
        @endif

        @if(session('warning'))
            <x-ui.alert variant="warning" :message="session('warning')" class="mb-4" />
        @endif

        @if(session('error'))
            <x-ui.alert variant="danger" :message="session('error')" class="mb-4" />
        @endif

        @if(!auth()->user()->hasVerifiedEmail())
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
