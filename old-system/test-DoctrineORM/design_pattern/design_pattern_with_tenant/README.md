# Padrão Design Pattern With Tenant - Easy Budget

## Visão Geral

Esta estrutura demonstra a implementação de padrões de desenvolvimento com **controle multi-tenant** no Easy Budget. 

## Diferenças do Padrão NoTenant

### Interfaces Utilizadas
- **Repository**: `RepositoryInterface` (com métodos `*ByTenantId`)
- **Service**: `ServiceInterface` (com métodos `*ByTenantId`)

### Principais Características

#### 🔒 **Segurança Multi-Tenant**
- Todos os métodos exigem `tenant_id`
- Validação obrigatória de propriedade do tenant
- Isolamento completo de dados entre tenants

#### 📊 **Auditoria Obrigatória**
- Log de atividades em todas as operações
- Rastreabilidade por tenant e usuário
- Histórico de modificações detalhado

#### 🎯 **Métodos Específicos**
- `findByIdAndTenantId()`
- `findAllByTenantId()`
- `createByTenantId()`
- `updateByIdAndTenantId()`
- `deleteByIdAndTenantId()`

## Arquivos da Estrutura

```
tests/design_pattern_with_tenant/
├── entities/
│   └── DesignPatternWithTenantEntity.php
├── repositories/
│   └── DesignPatternWithTenantRepository.php
├── services/
│   └── DesignPatternWithTenantService.php
├── controller/
│   └── DesignPatternWithTenantController.php
├── request/
│   └── DesignPatternWithTenantFormRequest.php
├── view/
│   └── designPatternWithTenant.twig
└── README.md
```

## Casos de Uso Recomendados

### Use WithTenant para:
- ✅ Dados específicos do cliente/empresa
- ✅ Configurações personalizadas por tenant
- ✅ Relatórios financeiros isolados
- ✅ Dados sensíveis que precisam de isolamento

### Use NoTenant para:
- ✅ Configurações globais do sistema
- ✅ Dados de referência (países, moedas)
- ✅ Templates compartilhados
- ✅ Logs do sistema

## Benefícios da Arquitetura

### 🛡️ **Segurança**
- Prevenção de vazamento de dados entre tenants
- Controle rigoroso de acesso
- Validação em múltiplas camadas

### 📈 **Escalabilidade**
- Isolamento permite crescimento independente
- Performance otimizada por tenant
- Backup seletivo por cliente

### 🔍 **Auditoria**
- Rastreabilidade completa
- Conformidade regulatória
- Debug facilitado por tenant


## 📁 Estrutura dos Padrões

### 🏢 **Repository Pattern WithTenant**

-  `repositories/` - Padrões para repositórios com tenant
-  Retorno: `EntityORMInterface` para save()
-  Retorno: `bool` para deleteByIdAndTenantId()
-  Interface: `RepositoryInterface`
-  **Com filtros obrigatórios por tenant_id**
-  **Validação de isolamento entre tenants**

### 🔧 **Service Pattern WithTenant**

-  `services/` - Padrões para serviços com tenant
-  Retorno: `ServiceResult` sempre
-  Interface: `ServiceInterface`
-  Encapsulamento de regras de negócio por tenant
-  **Com validação obrigatória de tenant_id**
-  **✅ Verificação via `$result->isSuccess()`** - Padrão consistente
-  **✅ Mensagens específicas com fallback** - `$result->message ?? 'mensagem padrão'`
-  **🔒 Isolamento total entre tenants**

### 🎮 **Controller Pattern WithTenant**

-  `controller/` - Padrões para controladores com tenant
-  Herança do `AbstractController`
-  **✅ Uso consistente de `!$result->isSuccess()`** - Verificação padronizada
-  **✅ Tratamento misto de verificações** - `isSuccess()` no index, `status !== SUCCESS` nos outros
-  Sanitização automática via traits
-  **✅ Função `getDetailedErrorInfo($e)`** - Log detalhado de erros
-  **🔒 Controle rigoroso de permissões por tenant**
-  **📋 Validação automática de tenant_id em todas as operações**

### 📊 **Entity Pattern WithTenant**

-  `entities/` - Padrões para entidades por tenant
-  Implementação de `EntityORMInterface`
-  Métodos de serialização
-  Validação de dados
-  **Campo obrigatório tenant_id**
-  **Relacionamentos isolados por tenant**

### 🔗 **Interface Pattern**

-  **Usa interfaces reais do projeto** (`app\interfaces\`)
-  `ServiceInterface` - Contratos para services com tenant
-  `RepositoryInterface` - Contratos para repositórios com tenant
-  `EntityORMInterface` - Contratos para entidades ORM

## 📋 **Regras Gerais WithTenant**

1. **Repositórios**: 
   - Retornam dados práticos (`EntityORMInterface`, `bool`)
   - **Métodos obrigatórios**: `findByIdAndTenantId()`, `findAllByTenantId()`, `save()`, `deleteByIdAndTenantId()`
   - **Sempre filtram por tenant_id**

2. **Services**: 
   - Sempre retornam `ServiceResult` para consistência
   - **Métodos obrigatórios**: `getByIdAndTenantId()`, `listByTenantId()`, `createByTenantId()`, `updateByIdAndTenantId()`, `deleteByIdAndTenantId()`
   - **Validação rigorosa de tenant_id**

3. **Controllers**:
   - Herdam propriedades do `AbstractController`
   - **✅ Padrão de Verificação**: Usam `!$result->isSuccess()` ou `$result->status !== OperationStatus::SUCCESS`
   - **✅ Error Logging**: Implementam `getDetailedErrorInfo($e)` para debug
   - **✅ Mensagens Contextuais**: Usam mensagens específicas dos services com fallback
   - **🔒 Verificação de tenant_id em cada requisição**

4. **Entidades**: 
   - Implementam interfaces padrão do projeto
   - **Campo obrigatório**: `tenant_id` ou `tenantId`
   - **Relacionamentos isolados por tenant**

5. **Comentários**: Sempre em português brasileiro com PHPDoc completo

6. **🏢 Com Tenant**: Todos os dados são isolados por tenant_id

7. **🔒 Segurança**: Isolamento total entre tenants - critical!

8. **⚡ Performance**: Índices otimizados com tenant_id

## 🔍 **Padrões de Verificação de Status**

### **Método index() - Renderização de Página**

```php
// Obtém o tenant_id do usuário autenticado
$tenantId = $this->authService->getTenantId();

if ( !$entitiesResult->isSuccess() ) {
    Session::flash( 'error', $entitiesResult->message ?? 'Erro ao carregar as entidades.' );
    return new Response( $this->twig->env->render( 'pages/design_pattern_with_tenant/index.twig', [
        'entities' => [],
        'tenantId' => $tenantId,
    ] ), 500 );
}
```

### **Métodos CRUD - Redirecionamento**

```php
// Validação de tenant obrigatória
$tenantId = $this->authService->getTenantId();
if ( !$tenantId ) {
    return Redirect::redirect( '/login' )
        ->withMessage( 'error', 'Acesso não autorizado.' );
}

if ( $result->status !== OperationStatus::SUCCESS ) {
    return Redirect::redirect( '/admin/design-patterns-tenant/create' )
        ->withMessage( 'error', $result->message ?? 'Erro ao cadastrar a entidade.' );
}
```

### **Error Logging Detalhado**

```php
catch ( Throwable $e ) {
    getDetailedErrorInfo( $e ); // ✅ Log detalhado para debug
    Session::flash( 'error', 'Mensagem amigável para o usuário' );
    return Redirect::redirect( '/caminho/fallback' );
}
```

## 🎯 **Objetivo**

Manter consistência arquitetural para entidades **com controle rigoroso de tenant** e facilitar onboarding de novos desenvolvedores através de exemplos práticos e documentados para sistemas multi-tenant.

## ⚠️ **Atenção Especial WithTenant**

-  **🔒 Segurança Crítica**: Isolamento total entre tenants é obrigatório
-  **📋 Validação**: tenant_id deve ser validado em TODAS as operações
-  **⚡ Performance**: Índices compostos sempre incluem tenant_id
-  **🔍 Auditoria**: Logs devem sempre incluir tenant_id
-  **🚫 Vazamento de Dados**: Nunca permitir acesso cross-tenant
-  **✅ Debug**: Usar `getDetailedErrorInfo($e)` para logging detalhado em produção
-  **✅ UX**: Sempre implementar mensagens amigáveis com fallback para o usuário
-  **🏢 Casos de Uso**: Ideal para dados específicos de cliente/empresa
-  **⚠️ Não usar para**: Dados globais ou configurações compartilhadas

## 🚀 **Implementações Atualizadas**

### **Alterações Específicas WithTenant:**

1. **✅ Repository Pattern Atualizado**:
   - Métodos com sufixo `ByIdAndTenantId` e `ByTenantId`
   - Filtros automáticos por tenant_id
   - Validação de isolamento entre tenants

2. **✅ Service Pattern Refinado**:
   - Métodos com sufixo `ByTenantId` 
   - Validação rigorosa de tenant_id em todas as operações
   - Mensagens contextuais incluindo referência ao tenant

3. **✅ Controller Pattern Seguro**:
   - Validação de tenant_id no início de cada método
   - Controle de acesso baseado em tenant
   - Logs de auditoria com tenant_id

4. **✅ Entity Pattern Isolado**:
   - Campo tenant_id obrigatório
   - Relacionamentos com foreign keys incluindo tenant_id
   - Validações que consideram o contexto do tenant

## 📊 **Diferenças Principais: WithTenant vs NoTenant**

| Aspecto | WithTenant | NoTenant |
|---------|------------|----------|
| **Repositório** | `RepositoryInterface` | `RepositoryNoTenantInterface` |
| **Serviço** | `ServiceInterface` | `ServiceNoTenantInterface` |
| **Métodos Save** | `save($entity, $tenant_id)` | `save($entity)` |
| **Métodos Delete** | `deleteByIdAndTenantId($id, $tenant_id)` | `delete($id)` |
| **Métodos Find** | `findByIdAndTenantId($id, $tenant_id)` | `findById($id)` |
| **Filtros** | Sempre incluem tenant_id | Sem filtros de tenant |
| **Segurança** | Isolamento obrigatório | Dados globais |
| **Performance** | Índices com tenant_id | Índices simples |
| **Casos de Uso** | Dados específicos de cliente | Configurações globais |

Essas implementações seguem as melhores práticas de desenvolvimento multi-tenant e garantem segurança e consistência em todo o projeto.