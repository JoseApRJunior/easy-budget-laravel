<x-app-layout title="Criar Conta">
    <x-auth.split-layout
        title="Criar uma Conta"
        subtitle="Preencha os dados abaixo para começar">

        <x-slot:welcome>
            <div class="mb-4">
                <i class="bi bi-graph-up display-3 text-white-50"></i>
            </div>
            <h1 class="h2 fw-bold mb-3">Bem-vindo ao Easy Budget!</h1>
            <p class="lead text-white-50 mb-4">
                Crie sua conta e comece a transformar a gestão do seu negócio hoje mesmo.
            </p>

            <div class="row text-start g-3 justify-content-center">
                <x-auth.feature-item label="Gestão financeira completa" />
                <x-auth.feature-item label="Controle de orçamentos" />
                <x-auth.feature-item label="Relatórios detalhados" />
                <x-auth.feature-item label="Suporte especializado" />
            </div>

            <div class="mt-5 text-white-50 small">
                <i class="bi bi-shield-check me-1"></i>
                Seus dados estão seguros conosco
            </div>
        </x-slot:welcome>

        <!-- Social Login -->
        <div class="mb-4">
            <x-ui.button href="{{ route('auth.google') }}" variant="outline-danger" size="lg" icon="google" label="Continuar com Google" class="w-100" />
            <x-auth.footer>
                Ao continuar com Google, você concorda com nossos
                <a href="/terms-of-service" target="_blank" class="text-decoration-none">Termos de Serviço</a>
                e
                <a href="/privacy-policy" target="_blank" class="text-decoration-none">Política de Privacidade</a>.
            </x-auth.footer>
        </div>

        <x-auth.divider label="ou preencha o formulário" />

        @if ($errors->any())
            <x-ui.alert variant="danger" title="Ops! Verifique os erros abaixo:">
                <ul class="mb-0 mt-2 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <x-ui.form.form :action="route('register.store')" class="needs-validation" novalidate>
            <!-- Nome e Sobrenome -->
            <div class="row g-3">
                <div class="col-md-6">
                    <x-ui.form.input-group name="first_name" label="Nome" placeholder="Seu nome" required :value="old('first_name')" icon="person" />
                </div>
                <div class="col-md-6">
                    <x-ui.form.input-group name="last_name" label="Sobrenome" placeholder="Seu sobrenome" required :value="old('last_name')" icon="person" />
                </div>
            </div>

            <!-- Email e Telefone -->
            <div class="row g-3">
                <div class="col-md-6">
                    <x-ui.form.input-group type="email" name="email" label="Email" placeholder="seu@email.com" required :value="old('email')" icon="envelope" />
                </div>
                <div class="col-md-6">
                    <x-ui.form.phone name="phone" label="Telefone" required :value="old('phone')" />
                </div>
            </div>

            <!-- Senha -->
            <div class="row g-3">
                <div class="col-md-6">
                    <x-ui.form.password
                        name="password"
                        id="password"
                        label="Senha"
                        required
                        showStrength="true"
                        autocomplete="new-password" />
                </div>

                <div class="col-md-6">
                    <x-ui.form.password
                        name="password_confirmation"
                        id="password_confirmation"
                        label="Confirmar Senha"
                        required
                        :confirmId="'password'"
                        autocomplete="new-password" />
                </div>
            </div>

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
