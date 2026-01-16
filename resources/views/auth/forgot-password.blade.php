<x-app-layout title="Recuperar Senha">
    <div class="container py-5">
        <x-auth.card 
            title="Esqueceu a senha?" 
            subtitle="Digite seu email e enviaremos um link para redefinir sua senha."
            icon="envelope-exclamation">
            
            <x-ui.form.form :action="route('password.email')">
                <x-ui.form.input-group 
                    type="email" 
                    name="email" 
                    label="Email" 
                    placeholder="seu@email.com" 
                    required 
                    :value="old('email')" 
                    icon="envelope"
                    autofocus />

                <x-ui.form.actions>
                    <x-ui.button type="submit" variant="primary" size="lg" icon="envelope" label="Enviar Link" />
                </x-ui.form.actions>

                <x-auth.footer 
                    text="Lembrou sua senha?" 
                    linkText="Voltar ao Login" 
                    :linkHref="route('login')" />
            </x-ui.form.form>
        </x-auth.card>
    </div>
</x-app-layout>
