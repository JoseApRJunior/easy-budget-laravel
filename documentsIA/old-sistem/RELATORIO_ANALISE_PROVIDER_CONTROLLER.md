# Relatório de Análise - ProviderController (Sistema Antigo)

## 📋 Sumário Executivo

**Arquivo:** `old-system/app/controllers/ProviderController.php`  
**Prioridade:** ⭐⭐⭐ CRÍTICO  
**Complexidade:** Alta  
**Status:** Parcialmente implementado no sistema atual

---

## 🎯 Visão Geral

### Responsabilidade
Gerenciar perfil completo do provider (prestador de serviços):
- Dashboard com resumo financeiro
- Atualização de dados pessoais e empresariais
- Upload de logo
- Alteração de senha

### Características
- ✅ Atualização multi-entidade (5 tabelas)
- ✅ Upload de imagem com redimensionamento
- ✅ Validação de duplicidade de email
- ✅ Comparação de dados antes de atualizar
- ✅ Limpeza de sessão após atualização
- ✅ Registro de atividades

---

## 📦 Dependências (15 total)

```php
1. Twig - Template engine
2. User - Model de usuários
3. CommonData - Dados comuns (CPF/CNPJ, etc)
4. Contact - Contatos
5. Address - Endereços
6. Provider - Providers
7. UserRegistrationService - Serviço de registro
8. UploadImage - Upload de imagens
9. Budget - Orçamentos
10. ActivityService - Logs
11. Activity - Model de atividades
12. FinancialSummary - Resumo financeiro
13. AreaOfActivity - Áreas de atuação
14. Profession - Profissões
15. Request - HTTP Request
```

---

## 📊 Métodos (6 total)

### 1. `index()` ⭐⭐⭐
**Rota:** GET `/provider`  
**View:** `pages/provider/index.twig`  
**Função:** Dashboard do provider

```php
public function index(): Response
{
    $budgets = $this->budget->getRecentBudgets($tenant_id, 1);
    $activities = $this->activity->getRecentActivities($tenant_id);
    $financial_summary = $this->financialSummary->getMonthlySummary($tenant_id);
    
    return render('pages/provider/index.twig', [
        'budgets' => $budgets,
        'activities' => $activities,
        'financial_summary' => $financial_summary,
    ]);
}
```

**Dados Exibidos:**
- Orçamentos recentes (1 página)
- Atividades recentes
- Resumo financeiro mensal

---

### 2. `update()` ⭐⭐⭐
**Rota:** GET `/provider/update`  
**View:** `pages/provider/update.twig`  
**Função:** Formulário de atualização

```php
public function update(): Response
{
    $provider = $this->provider->getProviderFullByUserId($user_id, $tenant_id);
    $areas_of_activity = $this->areaOfActivity->findAll();
    $professions = $this->profession->findAll();
    
    return render('pages/provider/update.twig', [
        'provider' => $provider,
        'areas_of_activity' => $areas_of_activity,
        'professions' => $professions,
    ]);
}
```

**Dados do Formulário:**
- Dados completos do provider
- Lista de áreas de atuação
- Lista de profissões

---

### 3. `update_store()` ⭐⭐⭐ COMPLEXO
**Rota:** POST `/provider/update`  
**Função:** Atualiza dados do provider

#### Fluxo Completo:
```
1. Valida formulário (UserWithProviderFormRequest)
2. Verifica duplicidade de email
3. Processa upload de logo (se houver)
   - Redimensiona para 200px largura
   - Remove logo antiga
4. Atualiza 5 entidades separadamente:
   a) User (dados do usuário)
   b) CommonData (CPF/CNPJ, área, profissão)
   c) Contact (telefones, emails)
   d) Address (endereço completo)
   e) Provider (dados específicos)
5. Compara dados antes de atualizar (evita updates desnecessários)
6. Registra atividade
7. Limpa sessão
8. Redireciona para /settings
```

#### Validações:
- Email único (exceto próprio)
- Dados obrigatórios via FormRequest
- Comparação de objetos antes de update

#### Upload de Logo:
```php
if ($this->request->hasFile('logo')) {
    $this->uploadImage->make('logo')
        ->resize(200, null, true)
        ->execute();
    $info = $this->uploadImage->get_image_info();
    $data['logo'] = $info['path'];
    
    // Remove logo antiga
    if ($originalData['logo'] !== null) {
        removeFile($originalData['logo']);
    }
}
```

#### Atualização Multi-Entidade:
```php
// Para cada entidade:
1. Busca dados atuais
2. Converte para array
3. Mescla com dados do formulário
4. Cria nova entidade
5. Compara com dados atuais
6. Atualiza apenas se houver mudanças
```

---

### 4. `change_password()` ⭐
**Rota:** GET `/provider/change-password`  
**View:** `pages/provider/change_password.twig`  
**Função:** Formulário de alteração de senha

---

### 5. `change_password_store()` ⭐⭐
**Rota:** POST `/provider/change-password`  
**Função:** Processa alteração de senha

#### Validações:
```php
1. Valida formulário (ProviderUpdatePasswordFormRequest)
2. Verifica se password === confirm_password
3. Verifica se nova senha !== senha atual
4. Atualiza senha via UserRegistrationService
5. Registra atividade
6. Envia email com nova senha
```

---

### 6. `activityLogger()` 🔒
**Função:** Helper para registrar atividades

---

## 🔄 Fluxo de Atualização de Perfil

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Provider acessa formulário                               │
│    GET /provider/update                                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. Sistema carrega dados completos                         │
│    - Provider full (5 tabelas JOIN)                         │
│    - Áreas de atuação                                       │
│    - Profissões                                             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Provider preenche formulário                            │
│    - Dados pessoais                                         │
│    - Dados empresariais                                     │
│    - Contatos                                               │
│    - Endereço                                               │
│    - Logo (opcional)                                        │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. POST /provider/update                                    │
│    - Valida formulário                                      │
│    - Verifica email duplicado                               │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. Processa upload de logo (se houver)                     │
│    - Redimensiona para 200px                                │
│    - Salva arquivo                                          │
│    - Remove logo antiga                                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. Atualiza User                                            │
│    - Busca dados atuais                                     │
│    - Compara com novos dados                                │
│    - Atualiza se houver mudanças                            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 7. Atualiza CommonData                                      │
│    - CPF/CNPJ, área, profissão                              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 8. Atualiza Contact                                         │
│    - Telefones, emails                                      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 9. Atualiza Address                                         │
│    - Endereço completo                                      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 10. Atualiza Provider                                       │
│     - Dados específicos do provider                         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 11. Registra atividade                                      │
│     - provider_updated                                      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 12. Limpa sessão                                            │
│     - checkPlan                                             │
│     - last_updated_session_provider                         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 13. Redireciona para /settings                              │
│     - Mensagem de sucesso                                   │
└─────────────────────────────────────────────────────────────┘
```

---

## ⚠️ Pontos Críticos

### 1. Atualização Multi-Entidade
**Complexidade:** ALTA  
**Risco:** Inconsistência de dados se uma atualização falhar  
**Solução Laravel:** Usar DB::transaction()

### 2. Comparação de Objetos
**Função:** `compareObjects()`  
**Objetivo:** Evitar updates desnecessários  
**Benefício:** Performance

### 3. Upload de Imagem
**Biblioteca:** UploadImage (custom)  
**Operações:**
- Redimensionamento automático
- Remoção de arquivo antigo
- Validação de tipo

### 4. Validação de Email
**Regra:** Email único, exceto o próprio  
**Implementação:**
```php
$checkObj = $this->user->getUserByEmail($data['email']);
if (!$checkObj instanceof EntityNotFound) {
    if ($checkObj->id != $this->authenticated->user_id) {
        return error('Email já registrado');
    }
}
```

### 5. Limpeza de Sessão
**Importante:** Limpa sessão após atualização  
**Variáveis:**
- `checkPlan`
- `last_updated_session_provider`

---

## 📝 Recomendações Laravel

### Controllers
```php
App\Http\Controllers\Provider\
├── DashboardController
│   └── index() - Dashboard
└── ProfileController
    ├── edit() - Formulário
    ├── update() - Atualizar perfil
    ├── editPassword() - Form senha
    └── updatePassword() - Atualizar senha
```

### Form Requests
```php
App\Http\Requests\Provider\
├── ProfileUpdateRequest
└── PasswordUpdateRequest
```

### Services
```php
App\Services\Domain\
└── ProviderProfileService
    ├── updateProfile() - Atualização completa
    ├── updatePassword() - Senha
    └── uploadLogo() - Upload de logo
```

### Events
```php
Events:
├── ProviderProfileUpdated
└── ProviderPasswordChanged

Listeners:
├── ClearProviderCache
└── SendPasswordChangeNotification
```

---

## ✅ Checklist de Implementação

- [ ] Criar ProfileController
- [ ] Criar DashboardController
- [ ] Criar ProfileUpdateRequest
- [ ] Criar PasswordUpdateRequest
- [ ] Criar ProviderProfileService
- [ ] Implementar upload de logo
- [ ] Implementar atualização multi-entidade com transaction
- [ ] Implementar comparação de dados
- [ ] Implementar validação de email único
- [ ] Criar events e listeners
- [ ] Criar views
- [ ] Implementar testes

---

## 🐛 Melhorias Identificadas

### 1. Usar Transações ⭐⭐⭐
**Atual:** Atualiza 5 entidades sem transaction  
**Proposta:** Envolver tudo em DB::transaction()  
**Benefício:** Consistência de dados

### 2. Separar Responsabilidades ⭐⭐
**Atual:** Controller faz tudo  
**Proposta:** Mover lógica para Service  
**Benefício:** Testabilidade

### 3. Usar Intervention Image ⭐⭐
**Atual:** UploadImage custom  
**Proposta:** Usar Intervention Image (já instalado)  
**Benefício:** Biblioteca mantida

### 4. Usar Eloquent Events ⭐
**Atual:** Limpeza manual de sessão  
**Proposta:** Event listeners  
**Benefício:** Desacoplamento

### 5. Validar em Policy ⭐
**Atual:** Validação no controller  
**Proposta:** ProfilePolicy  
**Benefício:** Reutilização

---

**Fim do Relatório**
