<x-app-layout title="Redefinir Senha">
    <x-auth.split-layout
        title="Redefinir Senha"
        subtitle="Crie uma nova senha forte e segura para sua conta.">

        <x-slot:welcome>
            <x-auth.welcome-header
                title="Quase lá!"
                subtitle="Estamos redefinindo sua segurança. Escolha uma senha que você não tenha usado antes."
            />

            <x-auth.feature-grid>
                <x-auth.feature-item label="Use pelo menos 8 caracteres" />
                <x-auth.feature-item label="Combine letras, números e símbolos" />
                <x-auth.feature-item label="Sua conta será atualizada instantaneamente" />
            </x-auth.feature-grid>

            <x-auth.security-note>
                A nova senha deve ser diferente das anteriores para maior segurança.
            </x-auth.security-note>
        </x-slot:welcome>

        <x-ui.form.form :action="route('password.store')">
            <input type="hidden" name="token" value="{{ $request->route('token') ?? $request->query('token') }}">

            <x-ui.form.input-group
                type="email"
                name="email"
                label="Endereço de E-mail"
                placeholder="exemplo@email.com"
                required
                :value="old('email', $request->email)"
                icon="envelope" />

            <x-ui.form.password
                name="password"
                id="password"
                label="Nova Senha"
                placeholder="Digite sua nova senha"
                required
                showStrength="true"
                autocomplete="new-password" />

            <x-ui.form.password
                name="password_confirmation"
                id="password_confirmation"
                label="Confirmar Nova Senha"
                placeholder="Repita a nova senha"
                required
                :confirmId="'password'"
                autocomplete="new-password" />

            <x-ui.form.actions class="mt-4">
                <x-ui.button type="submit" variant="primary" size="lg" icon="check2-circle" label="Atualizar Senha" class="w-100" />
            </x-ui.form.actions>

            <x-auth.footer
                text="Mudou de ideia?"
                linkText="Voltar ao Login"
                :linkHref="route('login')" />
        </x-ui.form.form>
    </x-auth.split-layout>
</x-app-layout>
