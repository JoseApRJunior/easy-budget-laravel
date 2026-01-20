<x-app-layout title="Login">
    <x-auth.split-layout
        title="Acesse sua Conta"
        subtitle="Entre com suas credenciais para continuar">

        <x-slot:welcome>
            <x-auth.welcome-header
                title="Bem-vindo de volta!"
                subtitle="Acesse seu painel para gerenciar seus orçamentos, clientes e pagamentos."

            />

            <x-auth.feature-grid>
                <x-auth.feature-item label="Dashboard em tempo real" />
                <x-auth.feature-item label="Gestão de orçamentos e serviços" />
                <x-auth.feature-item label="Histórico completo de pagamentos" />
            </x-auth.feature-grid>

            <x-auth.security-note>
                Sua sessão é protegida com criptografia SSL avançada.
            </x-auth.security-note>
        </x-slot:welcome>

        <x-ui.form.form :action="route('login')" id="loginForm">
            <!-- Social Login -->
            <x-ui.form.actions class="mb-4">
                <x-ui.button href="{{ route('auth.google') }}" variant="outline-danger" icon="google" label="Entrar com Google" class="w-100 py-2" />
            </x-ui.form.actions>

            <x-auth.divider />

            <!-- Email -->
            <x-ui.form.input-group
                name="email"
                type="email"
                label="Endereço de E-mail"
                icon="envelope"
                placeholder="exemplo@email.com"
                required
                autocomplete="email" />

            <!-- Senha -->
            <x-ui.form.password
                name="password"
                id="password-input"
                label="Senha"
                placeholder="Digite sua senha"
                required
                showForgot="true" />

            <!-- Remember Me -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <x-ui.form.checkbox
                    name="remember"
                    id="remember_me"
                    label="Lembrar de mim" />
            </div>

            <!-- Actions -->
            <x-ui.form.actions>
                <x-ui.button type="submit" variant="primary" size="lg" icon="box-arrow-in-right" label="Acessar Sistema" class="w-100" />
            </x-ui.form.actions>

            <x-auth.footer
                text="Não tem uma conta?"
                linkText="Criar conta agora"
                :linkHref="route('register')" />

            <x-auth.test-user />
        </x-ui.form.form>
    </x-auth.split-layout>
</x-app-layout>
