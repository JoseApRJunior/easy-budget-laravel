<x-app-layout title="Login">
    <x-auth.card
        title="Acesse sua Conta"
        subtitle="Entre com suas credenciais para continuar"
        icon="person-circle">

            <x-ui.form.form :action="route('login')" id="loginForm">
                <!-- Email -->
                <x-ui.form.input-group
                    name="email"
                    type="email"
                    label="Email"
                    icon="envelope"
                    placeholder="seu@email.com"
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
                <x-ui.form.checkbox
                    name="remember"
                    id="remember_me"
                    label="Lembrar de mim neste dispositivo" />

                <!-- Actions -->
                <x-ui.form.actions>
                    <x-ui.button type="submit" variant="primary" size="lg" icon="box-arrow-in-right" label="Entrar" />

                    <x-auth.divider />

                    <x-ui.button href="{{ route('auth.google') }}" variant="outline-danger" icon="google" label="Continuar com Google" />
                </x-ui.form.actions>

                <x-auth.footer
                    text="Não tem uma conta?"
                    linkText="Cadastre-se"
                    :linkHref="route('register')" />
            </x-ui.form.form>

            <x-auth.test-user />
        </x-auth.card>
</x-app-layout>
