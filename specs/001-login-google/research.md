# Research Document: Login com Google (OAuth 2.0)

**Feature**: `001-login-google`
**Created**: 2025-10-21
**Status**: In Progress
**Phase**: 0 - Research & Analysis

---

## 🎯 Research Findings

### Decision: Laravel Socialite Integration

**Escolha**: Utilizar Laravel Socialite para implementação OAuth 2.0

**Justificativa**:

-  Framework oficial do Laravel para autenticação social
-  Suporte nativo para Google OAuth 2.0
-  Integração perfeita com sistema de autenticação existente
-  Múltiplos providers suportados (preparado para futuras expansões)
-  Tratamento automático de state e tokens de segurança

**Alternativas consideradas**:

-  **Implementação customizada**: Mais controle, mas maior complexidade de manutenção
-  **Biblioteca externa (OAuth.io)**: Custos adicionais e dependência externa
-  **Google API Client direto**: Muito verboso e sem integração Laravel

**Implementação recomendada**:

```bash
composer require laravel/socialite
```

---

### Decision: Campos adicionais no modelo User

**Escolha**: Adicionar campos `google_id`, `avatar`, `google_data` ao modelo User

**Justificativa**:

-  `google_id`: Identificador único do Google para evitar duplicatas
-  `avatar`: URL da imagem do perfil para UX melhorada
-  `google_data`: JSON com dados adicionais (nome completo, locale, etc.)
-  Campos nullable para compatibilidade com usuários existentes

**Estrutura proposta**:

```php
// Migration para novos campos
Schema::table('users', function (Blueprint $table) {
    $table->string('google_id')->nullable()->unique();
    $table->string('avatar')->nullable();
    $table->json('google_data')->nullable();
});
```

**Índices recomendados**:

```php
// Para performance de lookups
$table->index('google_id');
```

---

### Decision: Fluxo de autenticação híbrido

**Escolha**: Manter sistema de autenticação atual + adicionar OAuth como opção

**Justificativa**:

-  Compatibilidade com usuários existentes
-  Opção de múltiplos métodos de login
-  Fallback em caso de problemas com Google
-  Melhor UX com opções variadas

**Fluxo proposto**:

1. Página de login oferece: "Entrar com Email/Senha" OU "Entrar com Google"
2. OAuth cria/vincula conta automaticamente
3. Sistema redireciona para dashboard independente do método

---

### Decision: Tratamento de usuários existentes

**Escolha**: Vinculação automática por e-mail + opção de desvinculação

**Justificativa**:

-  E-mail é identificador único confiável
-  Evita criação de contas duplicadas
-  Permite desvinculação futura se necessário
-  Mantém integridade dos dados

**Lógica de decisão**:

```php
// Pseudocódigo
$user = User::where('email', $googleEmail)->first();

if ($user) {
    // Vincular conta existente
    $user->update([
        'google_id' => $googleId,
        'avatar' => $avatar,
        'google_data' => $profileData
    ]);
} else {
    // Criar nova conta
    $user = User::create([
        'email' => $googleEmail,
        'name' => $googleName,
        'google_id' => $googleId,
        'avatar' => $avatar,
        'email_verified_at' => now(), // Google já verificou
        // ... outros campos obrigatórios
    ]);
}
```

---

### Decision: Configuração de ambiente OAuth

**Escolha**: Configuração diferenciada para desenvolvimento e produção

**Justificativa**:

-  Segurança: Client Secret diferente por ambiente
-  Desenvolvimento: URLs locais para teste
-  Produção: HTTPS obrigatório e domínios corretos

**Configuração recomendada**:

```php
// config/services.php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

**Variáveis de ambiente necessárias**:

```env
# Desenvolvimento
GOOGLE_CLIENT_ID=your-dev-client-id.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-dev-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

# Produção
GOOGLE_CLIENT_ID=your-prod-client-id.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-prod-client-secret
GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback
```

---

### Decision: Integração com sistema de auditoria

**Escolha**: Estender sistema de auditoria existente para OAuth

**Justificativa**:

-  Consistência com arquitetura atual
-  Rastreamento completo de todas as ações
-  Facilita debugging e segurança

**Categorias de auditoria propostas**:

-  `social_auth_attempt`: Tentativa de login social
-  `social_auth_success`: Login social bem-sucedido
-  `social_auth_link`: Vinculação de conta existente
-  `social_auth_create`: Criação de nova conta via OAuth
-  `social_auth_error`: Erro durante autenticação social

**Dados de auditoria recomendados**:

```php
[
    'provider' => 'google',
    'google_user_id' => $googleId,
    'email' => $email,
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
    'tenant_id' => $tenantId,
]
```

---

### Decision: Tratamento de erros OAuth

**Escolha**: Tratamento granular com mensagens amigáveis

**Justificativa**:

-  Melhor UX com mensagens claras
-  Debugging facilitado com logs detalhados
-  Tratamento específico para diferentes tipos de erro

**Categorias de erro identificadas**:

1. **Usuário cancela autenticação**: "Login com Google cancelado pelo usuário"
2. **Erro de configuração OAuth**: "Serviço temporariamente indisponível"
3. **E-mail já associado**: "Este e-mail já está vinculado a uma conta"
4. **Dados insuficientes do Google**: "Não foi possível obter dados do Google"
5. **Token inválido/expirado**: "Sessão expirada, tente novamente"

---

### Decision: Sincronização de dados do perfil

**Escolha**: Sincronização automática + refresh periódico

**Justificativa**:

-  Dados sempre atualizados do Google
-  Melhor experiência do usuário
-  Reduz necessidade de edição manual

**Dados a sincronizar**:

-  **Nome completo** (given_name + family_name)
-  **Avatar/imagem do perfil**
-  **E-mail verificado** (marcação automática)
-  **Locale/idioma** (para futuras internacionalizações)

**Frequência de sincronização**:

-  **Login**: Sempre sincronizar dados atuais
-  **Refresh token**: Opcional, apenas se necessário para funcionalidades futuras

---

### Decision: Segurança e privacidade

**Escolha**: Princípio de minimalismo + transparência

**Justificativa**:

-  Armazenar apenas dados necessários
-  Transparência sobre uso de dados
-  Conformidade com políticas de privacidade

**Dados que NÃO armazenar**:

-  Lista de contatos do Google
-  Dados de localização em tempo real
-  Histórico de atividades do Google
-  Informações financeiras

**Dados que armazenar**:

-  Apenas ID do usuário, nome, e-mail e avatar
-  Dados necessários para funcionamento do sistema

---

## 🔧 Technical Specifications

### API Endpoints Necessários

```php
// Rotas propostas
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/google', [GoogleAuthController::class, 'redirect'])->name('google');
    Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
    Route::post('/google/unlink', [GoogleAuthController::class, 'unlink'])->name('google.unlink');
});
```

### Service Layer Design

**GoogleAuthService** - Responsabilidades:

-  Gerenciar fluxo OAuth com Google
-  Criar/vincular usuários automaticamente
-  Sincronizar dados do perfil
-  Integrar com sistema de auditoria
-  Tratar erros e exceções

**Métodos principais**:

```php
interface GoogleAuthService
{
    public function redirectToGoogle(): RedirectResponse;
    public function handleGoogleCallback(string $code): ServiceResult;
    public function linkExistingAccount(User $user, array $googleData): ServiceResult;
    public function createNewAccount(array $googleData): ServiceResult;
    public function unlinkGoogleAccount(User $user): ServiceResult;
}
```

### Model Updates

**User Model** - Novos métodos:

```php
public function hasGoogleAccount(): bool
public function linkGoogleAccount(array $data): bool
public function unlinkGoogleAccount(): bool
public function syncGoogleData(array $data): bool
public function getGoogleProfile(): ?array
```

---

## 📋 Implementation Checklist

### Pré-requisitos

-  [ ] Configurar projeto no Google Cloud Console
-  [ ] Obter OAuth 2.0 credentials (Client ID, Client Secret)
-  [ ] Instalar Laravel Socialite
-  [ ] Configurar serviços Google no Laravel
-  [ ] Criar migration para novos campos do User

### Desenvolvimento

-  [ ] Implementar GoogleAuthService
-  [ ] Criar GoogleAuthController
-  [ ] Atualizar modelo User
-  [ ] Implementar views de login com botão Google
-  [ ] Adicionar tratamento de erros
-  [ ] Implementar sistema de auditoria para OAuth

### Testes

-  [ ] Testes de integração OAuth
-  [ ] Testes de criação de usuários
-  [ ] Testes de vinculação de contas
-  [ ] Testes de tratamento de erros
-  [ ] Testes de segurança e privacidade

### Documentação

-  [ ] Documentar configuração OAuth
-  [ ] Criar guia de setup para produção
-  [ ] Documentar troubleshooting
-  [ ] Atualizar documentação de autenticação

---

## 🚨 Open Questions

**QA-001**: Como configurar múltiplos ambientes (dev/staging/prod) com OAuth?
**QA-002**: Necessário implementar rate limiting específico para OAuth?
**QA-003**: Como lidar com mudanças na API do Google (versioning)?
**QA-004**: Implementar métricas de uso do login social?

---

_Este documento será atualizado conforme novas descobertas de pesquisa_
