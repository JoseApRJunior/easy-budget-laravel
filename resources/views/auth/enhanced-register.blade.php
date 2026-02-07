<x-app-layout title="Criar Conta">
    <x-auth.split-layout
        title="Criar uma Conta"
        subtitle="Preencha os dados abaixo para começar">

        <x-slot:welcome>
            <x-auth.welcome-header
                title="Bem-vindo ao Easy Budget!"
                subtitle="Crie sua conta e comece a transformar a gestão do seu negócio hoje mesmo."
            />

            <x-auth.feature-grid>
                <x-auth.feature-item label="Orçamentos e Serviços profissionais e aprovação online" />
                <x-auth.feature-item label="Pagamentos integrados via Mercado Pago" />
                <x-auth.feature-item label="Relatórios e Dashboard em tempo real" />
                <x-auth.feature-item label="Gestão completa de clientes e estoque" />
            </x-auth.feature-grid>

            <x-auth.security-note>
                Seus dados estão seguros conosco
            </x-auth.security-note>
        </x-slot:welcome>

        <!-- Social Login -->
        <x-ui.form.actions class="mb-2">
            <x-ui.button href="{{ route('auth.google') }}" variant="outline-danger" size="lg" icon="google" label="Continuar com Google" class="w-100 py-3 fw-semibold" />
            <div class="mt-3">
                <x-auth.footer>
                    <x-auth.legal-links mode="full" />
                </x-auth.footer>
            </div>
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
                    Eu li e aceito os <x-auth.legal-links mode="links" />
                </x-slot:label>
            </x-ui.form.checkbox>

            <x-ui.form.actions class="mt-4 mb-4">
                <x-ui.button type="submit" variant="primary" size="lg" icon="person-plus" label="Criar minha conta agora" class="w-100 py-3 fw-bold" />
            </x-ui.form.actions>

            <x-auth.footer
                text="Já tem uma conta?"
                linkText="Fazer login"
                :linkHref="route('login')" />
        </x-ui.form.form>
    </x-auth.split-layout>
</x-app-layout>
