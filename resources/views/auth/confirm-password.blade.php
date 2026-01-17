<x-app-layout title="Confirmar Senha">
    <x-auth.split-layout
        title="Confirmar Senha"
        subtitle="Esta é uma área segura do sistema. Por favor, confirme sua senha antes de continuar.">

        <x-slot:welcome>
            <x-auth.welcome-header
                title="Segurança em Primeiro Lugar"
                subtitle="Para proteger seus dados sensíveis, precisamos confirmar que é realmente você."
            />

            <x-auth.feature-grid>
                <x-auth.feature-item label="Acesso restrito a áreas críticas" />
                <x-auth.feature-item label="Proteção de dados financeiros" />
                <x-auth.feature-item label="Confirmação de identidade em tempo real" />
            </x-auth.feature-grid>

            <x-auth.security-note>
                Sua segurança é nossa prioridade. Nunca compartilhe sua senha.
            </x-auth.security-note>
        </x-slot:welcome>

        <x-ui.form.form :action="route('password.confirm')" id="confirmPasswordForm">
            <!-- Senha -->
            <x-ui.form.password
                name="password"
                id="password-input"
                label="Sua Senha"
                placeholder="Digite sua senha para confirmar"
                required
                autocomplete="current-password"
                :showForgot="true" />

            <!-- Actions -->
            <x-ui.form.actions class="mt-4">
                <x-ui.button type="submit" variant="primary" size="lg" icon="shield-lock" label="Confirmar Identidade" class="w-100" />
            </x-ui.form.actions>
        </x-ui.form.form>
    </x-auth.split-layout>
</x-app-layout>
