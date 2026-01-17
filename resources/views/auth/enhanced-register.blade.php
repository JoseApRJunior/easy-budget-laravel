<x-app-layout title="Criar Conta">
    <x-auth.split-layout
        title="Criar uma Conta"
        subtitle="Preencha os dados abaixo para começar">

        <x-slot:welcome>
            <x-auth.welcome-header
                icon="graph-up"
                title="Bem-vindo ao Easy Budget!"
                subtitle="Crie sua conta e comece a transformar a gestão do seu negócio hoje mesmo."
            />

            <x-auth.feature-grid>
                <x-auth.feature-item label="Gestão financeira completa" />
                <x-auth.feature-item label="Controle de orçamentos" />
                <x-auth.feature-item label="Relatórios detalhados" />
                <x-auth.feature-item label="Suporte especializado" />
            </x-auth.feature-grid>

            <x-auth.security-note>
                Seus dados estão seguros conosco
            </x-auth.security-note>
        </x-slot:welcome>

        <!-- Social Login -->
        <x-ui.form.actions class="mb-4">
            <x-ui.button href="{{ route('auth.google') }}" variant="outline-danger" size="lg" icon="google" label="Continuar com Google" class="w-100" />
            <x-auth.footer>
                Ao continuar com Google, você concorda com nossos
                <a href="/terms-of-service" target="_blank" class="text-decoration-none">Termos de Serviço</a>
                e
                <a href="/privacy-policy" target="_blank" class="text-decoration-none">Política de Privacidade</a>.
            </x-auth.footer>
        </x-ui.form.actions>

        <x-auth.divider label="ou preencha o formulário" />

        <x-ui.form.errors />

        <x-ui.form.form :action="route('register.store')" class="needs-validation" novalidate>
            <!-- Nome e Sobrenome -->
            <x-ui.form.row>
                <x-slot:left>
                    <x-ui.form.input-group name="first_name" label="Nome" placeholder="Seu nome" required :value="old('first_name')" icon="person" />
                </x-slot:left>
                <x-slot:right>
                    <x-ui.form.input-group name="last_name" label="Sobrenome" placeholder="Seu sobrenome" required :value="old('last_name')" icon="person" />
                </x-slot:right>
            </x-ui.form.row>

            <!-- Email e Telefone -->
            <x-ui.form.row>
                <x-slot:left>
                    <x-ui.form.input-group type="email" name="email" label="Email" placeholder="seu@email.com" required :value="old('email')" icon="envelope" />
                </x-slot:left>
                <x-slot:right>
                    <x-ui.form.phone name="phone" label="Telefone" required :value="old('phone')" />
                </x-slot:right>
            </x-ui.form.row>

            <!-- Senha -->
            <x-ui.form.row>
                <x-slot:left>
                    <x-ui.form.password
                        name="password"
                        id="password"
                        label="Senha"
                        required
                        showStrength="true"
                        autocomplete="new-password" />
                </x-slot:left>
                <x-slot:right>
                    <x-ui.form.password
                        name="password_confirmation"
                        id="password_confirmation"
                        label="Confirmar Senha"
                        required
                        :confirmId="'password'"
                        autocomplete="new-password" />
                </x-slot:right>
            </x-ui.form.row>

            <!-- Termos -->
            <x-ui.form.checkbox
                name="terms_accepted"
                required>
                <x-slot:label>
                    Eu li e aceito os <a href="/terms-of-service" target="_blank" class="text-decoration-none">Termos de Serviço</a> e a <a href="/privacy-policy" target="_blank" class="text-decoration-none">Política de Privacidade</a>.
                </x-slot:label>
            </x-ui.form.checkbox>

            <x-ui.form.actions class="mb-4">
                <x-ui.button type="submit" variant="primary" size="lg" icon="person-plus" label="Criar Conta" />
            </x-ui.form.actions>

            <x-auth.footer
                text="Já tem uma conta?"
                linkText="Fazer login"
                :linkHref="route('login')" />
        </x-ui.form.form>
    </x-auth.split-layout>
</x-app-layout>
