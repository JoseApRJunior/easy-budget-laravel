<x-app-layout title="Recuperar Senha">
    <x-auth.split-layout
        title="Esqueceu a senha?"
        subtitle="Digite seu email e enviaremos um link para redefinir sua senha.">

        <x-slot:welcome>
            <x-auth.welcome-header
                title="Recupere seu acesso"
                subtitle="Não se preocupe, acontece com os melhores. Vamos te ajudar a voltar para sua conta rapidinho."
            />

            <x-auth.feature-grid>
                <x-auth.feature-item label="Recuperação segura via E-mail" />
                <x-auth.feature-item label="Link de redefinição válido por 60 minutos" />
                <x-auth.feature-item label="Proteção de conta em duas etapas" />
            </x-auth.feature-grid>

            <x-auth.security-note>
                Seu e-mail está protegido por criptografia de ponta a ponta.
            </x-auth.security-note>
        </x-slot:welcome>

        <x-ui.form.form :action="route('password.email')">
            <x-ui.form.input-group
                type="email"
                name="email"
                label="Endereço de E-mail"
                placeholder="exemplo@email.com"
                required
                :value="old('email')"
                icon="envelope"
                autofocus />

            <x-ui.form.actions class="mt-4">
                <x-ui.button type="submit" variant="primary" size="lg" icon="send" label="Enviar Link de Recuperação" class="w-100" />
            </x-ui.form.actions>

            <x-auth.footer
                text="Lembrou sua senha?"
                linkText="Voltar ao Login"
                :linkHref="route('login')" />
        </x-ui.form.form>
    </x-auth.split-layout>
</x-app-layout>
