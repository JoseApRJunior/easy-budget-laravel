<div class="tab-pane fade" id="seguranca">
<style>
.password-rules ul {
   padding-left: 0;
   list-style: none;
}
.password-rules .rule-item {
   margin-bottom: 0.25rem;
   font-size: 0.875rem;
}
.password-rules .rule-item i {
   width: 16px;
   text-align: center;
}
.password-toggle {
   border-left: 0;
   border-top-left-radius: 0;
   border-bottom-left-radius: 0;
   min-width: 45px;
   display: flex;
   align-items: center;
   justify-content: center;
}
.password-toggle:hover {
   background-color: #e9ecef;
}
.password-toggle:focus {
   box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.input-group .form-control {
   border-right: 0;
}
.input-group .form-control:focus {
   box-shadow: none;
   border-color: #86b7fe;
}
.input-group .form-control:focus + .password-toggle {
   border-color: #86b7fe;
   box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.password-match-feedback, .password-mismatch-feedback {
   font-size: 0.875rem;
}
</style>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0">
            <h3 class="h5 mb-0">Segurança</h3>
            <p class="text-muted small mb-0 mt-1">Gerencie suas configurações de segurança e privacidade</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.security.update') }}">
                @csrf

                <!-- Alteração de Senha -->
                <div class="mb-4">
                    <h5 class="h6 text-primary mb-3">Alterar Senha</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="current_password" class="form-label">Senha Atual</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password"
                                       placeholder="Digite sua senha atual" autocomplete="current-password">
                                <button type="button" class="btn btn-outline-secondary password-toggle" data-input="current_password" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="new_password" class="form-label">Nova Senha</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password"
                                       placeholder="Digite a nova senha" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary password-toggle" data-input="new_password" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="password-rules mt-2" style="display:none;">
                                <small class="text-muted">Sua senha deve conter:</small>
                                <ul class="list-unstyled mb-0">
                                    <li class="rule-item" data-rule="length">
                                        <i class="bi bi-circle text-secondary me-1"></i>
                                        Pelo menos 6 caracteres
                                    </li>
                                    <li class="rule-item" data-rule="lowercase">
                                        <i class="bi bi-circle text-secondary me-1"></i>
                                        Letras minúsculas (a-z)
                                    </li>
                                    <li class="rule-item" data-rule="uppercase">
                                        <i class="bi bi-circle text-secondary me-1"></i>
                                        Letras maiúsculas (A-Z)
                                    </li>
                                    <li class="rule-item" data-rule="numbers">
                                        <i class="bi bi-circle text-secondary me-1"></i>
                                        Números (0-9)
                                    </li>
                                    <li class="rule-item" data-rule="special">
                                        <i class="bi bi-circle text-secondary me-1"></i>
                                        Caracteres especiais (@#$!%*?&)
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="new_password_confirmation" class="form-label">Confirmar Nova Senha</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password_confirmation"
                                       name="new_password_confirmation" placeholder="Confirme a nova senha" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary password-toggle" data-input="new_password_confirmation" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="password-match-feedback mt-1" style="display:none;">
                                <small class="text-success">
                                    <i class="bi bi-check-circle"></i> Senhas coincidem
                                </small>
                            </div>
                            <div class="password-mismatch-feedback mt-1" style="display:none;">
                                <small class="text-danger">
                                    <i class="bi bi-x-circle"></i> Senhas não coincidem
                                </small>
                            </div>
                        </div>
                    </div>
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
</div>
