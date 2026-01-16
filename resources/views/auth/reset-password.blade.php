<x-app-layout title="Redefinir Senha">
    <div class="container py-5">
        <x-auth.card 
            title="Redefinir Senha" 
            subtitle="Crie uma nova senha forte e segura para sua conta."
            icon="key">
            
            <x-ui.form.form :action="route('password.store')">
                <input type="hidden" name="token" value="{{ $request->route('token') ?? $request->query('token') }}">

                <x-ui.form.input-group 
                    type="email" 
                    name="email" 
                    label="Email" 
                    placeholder="seu@email.com" 
                    required 
                    :value="old('email', $request->email)" 
                    icon="envelope" />

                <x-ui.form.password 
                    name="password" 
                    id="password" 
                    label="Nova Senha" 
                    placeholder="Nova senha" 
                    required 
                    showStrength="true" 
                    autocomplete="new-password" />

                <x-ui.form.password 
                    name="password_confirmation" 
                    id="password_confirmation" 
                    label="Confirmar Senha" 
                    placeholder="Confirme a nova senha" 
                    required 
                    :confirmId="'password'" 
                    autocomplete="new-password" />

                <x-ui.form.actions>
                    <x-ui.button type="submit" variant="primary" size="lg" icon="check-circle" label="Redefinir Senha" />
                </x-ui.form.actions>

                <x-auth.footer 
                    text="Deseja cancelar?" 
                    linkText="Voltar ao Login" 
                    :linkHref="route('login')" />
            </x-ui.form.form>
        </x-auth.card>
    </div>
</x-app-layout>
