    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0">
            <h3 class="h5 mb-0">Segurança</h3>
            <p class="text-muted small mb-0 mt-1">Gerencie suas configurações de segurança e privacidade</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.security.update') }}">
                @csrf

                <!-- Alteração de Senha (link simples) -->
                <div class="mb-4">
                    <h5 class="h6 text-primary mb-2">Alterar Senha</h5>
                    <p class="text-muted mb-2">Gerencie sua senha de acesso em uma página dedicada.</p>
                    <a href="{{ url('/provider/change-password') }}" class="link-secondary">
                        <i class="bi bi-key me-1"></i>Alterar Senha
                    </a>
                </div>

                <hr class="my-4">

                <!-- Configurações de Notificações de Segurança -->
                <div class="mb-4">
                    <h5 class="h6 text-primary mb-3">Alertas de Segurança</h5>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="security_alerts" name="security_alerts"
                               value="1" {{ old('security_alerts', $tabs['security']['data']['user_settings']['security_alerts'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="security_alerts">
                            Receber alertas de segurança por email
                        </label>
                        <small class="form-text text-muted d-block">
                            Notificações sobre tentativas de login suspeitas e alterações de segurança
                        </small>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Sessões Ativas -->
                <div class="mb-4">
                    <h5 class="h6 text-primary mb-3">Sessões Ativas</h5>
                    @if(!empty($tabs['security']['data']['sessions']))
                        @foreach($tabs['security']['data']['sessions'] as $session)
                            <div class="d-flex align-items-center justify-content-between p-3 border rounded mb-2">
                                <div>
                                    <div class="fw-semibold">
                                        {{ $session['device'] ?? 'Dispositivo Desconhecido' }}
                                        @if($session['is_current'] ?? false)
                                            <span class="badge bg-success ms-2">Atual</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">
                                        {{ $session['ip_address'] ?? 'IP Desconhecido' }} •
                                        {{ $session['last_activity'] ? \Carbon\Carbon::parse($session['last_activity'])->diffForHumans() : 'Desconhecido' }}
                                    </small>
                                </div>
                                @if(!($session['is_current'] ?? false))
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Tem certeza que deseja encerrar esta sessão?')">
                                        <i class="bi bi-x-circle me-1"></i>Encerrar
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-shield-check text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2 mb-0">Nenhuma sessão ativa encontrada.</p>
                        </div>
                    @endif
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
