@props([
    'title' => 'Tudo o que você precisa para crescer',
    'subtitle' => 'Funcionalidades pensadas para simplificar seu dia a dia e profissionalizar seu negócio'
])

<section id="features" {{ $attributes->merge(['class' => 'py-5']) }}>
    <div class="main-container">
        <div class="section-header text-center mb-5">
            <h2 class="display-6 fw-bold text-dark">{{ $title }}</h2>
            <p class="small-text">{{ $subtitle }}</p>
        </div>

        <div class="row g-4">
            {{-- Gestão de Orçamentos --}}
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-card p-4">
                    <div class="feature-icon-container mb-3">
                        <i class="bi bi-file-earmark-text text-primary fs-3"></i>
                    </div>
                    <h3 class="h5 fw-bold mb-2">Orçamentos Profissionais</h3>
                    <p class="text-muted small">Crie e envie orçamentos personalizados em segundos. Acompanhe o status e receba aprovações online.</p>
                </div>
            </div>

            {{-- Clientes e Fornecedores --}}
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-card p-4">
                    <div class="feature-icon-container mb-3">
                        <i class="bi bi-people text-success fs-3"></i>
                    </div>
                    <h3 class="h5 fw-bold mb-2">Gestão de Contatos</h3>
                    <p class="text-muted small">Cadastro completo de clientes e fornecedores. Histórico de serviços e facilidade na comunicação.</p>
                </div>
            </div>

            {{-- Controle Financeiro --}}
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-card p-4">
                    <div class="feature-icon-container mb-3">
                        <i class="bi bi-graph-up-arrow text-info fs-3"></i>
                    </div>
                    <h3 class="h5 fw-bold mb-2">Controle Financeiro</h3>
                    <p class="text-muted small">Fluxo de caixa, contas a pagar e receber. Relatórios automáticos para você não perder nenhum centavo.</p>
                </div>
            </div>

            {{-- IA Analytics --}}
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-card p-4">
                    <div class="feature-icon-container mb-3">
                        <i class="bi bi-robot text-warning fs-3"></i>
                    </div>
                    <h3 class="h5 fw-bold mb-2">IA Analytics</h3>
                    <p class="text-muted small">Insights inteligentes gerados por IA para ajudar na tomada de decisões e previsão de faturamento.</p>
                </div>
            </div>

            {{-- Mobile Friendly --}}
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-card p-4">
                    <div class="feature-icon-container mb-3">
                        <i class="bi bi-phone text-danger fs-3"></i>
                    </div>
                    <h3 class="h5 fw-bold mb-2">Acesso de Qualquer Lugar</h3>
                    <p class="text-muted small">Interface responsiva para você gerenciar seu negócio do computador, tablet ou smartphone.</p>
                </div>
            </div>

            {{-- Segurança --}}
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-card p-4">
                    <div class="feature-icon-container mb-3">
                        <i class="bi bi-shield-check text-secondary fs-3"></i>
                    </div>
                    <h3 class="h5 fw-bold mb-2">Segurança de Dados</h3>
                    <p class="text-muted small">Seus dados protegidos com criptografia e backups automáticos. Foco total na sua tranquilidade.</p>
                </div>
            </div>
        </div>
    </div>
</section>
