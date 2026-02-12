<x-app-layout>
    <x-layout.page-container>
        <x-layout.page-header
            title="Integração Mercado Pago"
            icon="credit-card-2-front-fill"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Configurações' => url('/settings'),
                'Mercado Pago' => '#'
            ]">
                </x-layout.page-header>
        <x-layout.grid-row>
            <x-layout.grid-col size="col-12">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-link-45deg me-2"></i>Status da Conexão
                        </h5>
                    </x-slot:header>

                    <div class="p-2">
                        @if ($isConnected)
                            <x-ui.alert type="success" no-container>
                                <strong class="d-block">Conta Mercado Pago conectada</strong>
                                <span class="small">Sua conta está ativa para gerar faturas e receber pagamentos.</span>
                            </x-ui.alert>

                            <div class="bg-light p-4 rounded-3 mb-4 border">
                                <x-layout.grid-row class="g-4">
                                    @if (!empty($public_key))
                                        <x-layout.grid-col size="col-md-6">
                                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Chave Pública</label>
                                            <div class="d-flex align-items-center">
                                                <code class="bg-white px-3 py-2 rounded border flex-grow-1 text-dark">{{ $public_key }}</code>
                                                <x-ui.button variant="outline-secondary" size="sm" class="ms-2" onclick="copyToClipboard('{{ $public_key }}')" title="Copiar" icon="clipboard" feature="manage-settings" />
                                            </div>
                                        </x-layout.grid-col>
                                    @endif
                                    @if ($expires_readable)
                                        <x-layout.grid-col size="col-md-6">
                                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Expiração do Token</label>
                                            <div class="d-flex align-items-center bg-white px-3 py-2 rounded border">
                                                <i class="bi bi-clock me-2 text-primary"></i>
                                                <span class="text-dark">{{ $expires_readable }}</span>
                                            </div>
                                        </x-layout.grid-col>
                                    @endif
                                </x-layout.grid-row>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <form action="{{ route('integrations.mercadopago.refresh') }}" method="POST" class="d-inline">
                                    @csrf
                                    <x-ui.button type="submit" variant="primary" icon="arrow-repeat" label="Renovar Conexão" :disabled="!$can_refresh" feature="manage-settings" />
                                </form>
                                <form action="{{ route('integrations.mercadopago.disconnect') }}" method="POST" class="d-inline" id="form-disconnect-mp">
                                    @csrf
                                    <x-ui.button type="button" variant="outline-danger" icon="plug" label="Desconectar Conta" onclick="confirmDisconnect()" feature="manage-settings" />
                                </form>
                            </div>
                        @else
                            <x-resource.empty-state
                                icon="plug"
                                title="Integração não configurada"
                                description="Conecte sua conta Mercado Pago para automatizar o recebimento de faturas e oferecer mais opções de pagamento aos seus clientes."
                                icon-size="4rem"
                            >
                                <div class="bg-light p-4 rounded-3 mb-4 text-start mx-auto border" style="max-width: 600px;">
                                    <h6 class="fw-bold mb-3"><i class="bi bi-shield-check me-2 text-success"></i>Segurança e Privacidade</h6>
                                    <ul class="list-unstyled mb-0 small">
                                        <li class="mb-2 d-flex align-items-start">
                                            <i class="bi bi-check2 text-success me-2 mt-1"></i>
                                            <span>Não temos acesso à sua senha do Mercado Pago.</span>
                                        </li>
                                        <li class="mb-2 d-flex align-items-start">
                                            <i class="bi bi-check2 text-success me-2 mt-1"></i>
                                            <span>A conexão é feita através de um ambiente seguro do próprio Mercado Pago.</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <i class="bi bi-check2 text-success me-2 mt-1"></i>
                                            <span>Você pode desconectar sua conta a qualquer momento.</span>
                                        </li>
                                    </ul>
                                </div>

                                <x-ui.button :href="$authorization_url" variant="primary" size="lg" icon="link-45deg" label="Conectar Agora" feature="manage-settings" />
                            </x-resource.empty-state>
                        @endif
                    </div>

                    <x-slot:footer>
                        <div class="d-flex align-items-center text-muted small">
                            <i class="bi bi-info-circle me-2"></i>
                            <span>Ao conectar, você concorda com os termos de integração. Taxas de transação podem ser aplicadas pelo gateway.</span>
                        </div>
                    </x-slot:footer>
                </x-ui.card>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-layout.page-container>

    @push('scripts')
    <script>
        function confirmDisconnect() {
            Swal.fire({
                title: 'Desconectar Mercado Pago?',
                text: "Você precisará refazer a conexão para processar pagamentos automaticamente.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--text-error)',
                cancelButtonColor: 'var(--secondary-color)',
                confirmButtonText: 'Sim, desconectar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form-disconnect-mp').submit();
                }
            });
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Copiado!',
                        text: 'Chave copiada para a área de transferência.',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    alert('Chave copiada para a área de transferência!');
                }
            });
        }
    </script>
    @endpush
</x-app-layout>
