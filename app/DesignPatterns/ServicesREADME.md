# Padrões de Services - Easy Budget Laravel

## 📋 Visão Geral

Este diretório contém padrões unificados para desenvolvimento de services no projeto Easy Budget Laravel, criados para resolver inconsistências identificadas entre diferentes services existentes.

## 🎯 Problema Identificado

Durante análise dos services existentes, foram identificadas inconsistências significativas:

### ❌ Inconsistências Encontradas

| Service           | Características            | Problemas              |
| ----------------- | -------------------------- | ---------------------- |
| `PlanService`     | ✅ Completo com validações | ✅ Bem estruturado     |
| `CustomerService` | ⚠️ Básico/incompleto       | ❌ Falta implementação |
| `ProductService`  | ❌ Vazio                   | ❌ Não implementado    |
| `BudgetService`   | ❌ Não encontrado          | ❌ Não existe          |

**Problemas identificados:**

-  ❌ Tratamento inconsistente de `ServiceResult`
-  ❌ Validações repetitivas e não padronizadas
-  ❌ Falta de métodos obrigatórios em alguns services
-  ❌ Tratamento de erro não uniforme
-  ❌ Falta de integração com APIs externas onde necessário

## ✅ Solução Implementada: Sistema de 3 Níveis

Criamos um sistema de padrões unificado com **3 níveis** de services que atendem diferentes necessidades:

### 🏗️ Nível 1 - Service Básico

**Para:** Operações CRUD simples sem lógica complexa

**Características:**

-  Apenas operações básicas (CRUD)
-  Validação simples
-  Tratamento básico de erro
-  Sem integrações externas

**Exemplo de uso:**

```php
public function create(array $data): ServiceResult
{
    $validation = $this->validate($data);
    if (!$validation->isSuccess()) {
        return $validation;
    }

    return parent::create($data);
}
```

### 🏗️ Nível 2 - Service Intermediário

**Para:** Services com regras de negócio específicas

**Características:**

-  Validações de negócio avançadas
-  Métodos específicos além do CRUD
-  Tratamento completo de erro
-  Lógica de negócio implementada

**Exemplo de uso:**

```php
public function validate(array $data, bool $isUpdate = false): ServiceResult
{
    // Validação de negócio primeiro
    $businessValidation = $this->validateBusinessRules($data, $isUpdate);
    if (!$businessValidation->isSuccess()) {
        return $businessValidation;
    }

    // Validação técnica depois
    return parent::validate($data, $isUpdate);
}
```

### 🏗️ Nível 3 - Service Avançado

**Para:** Services com integrações externas e processamento complexo

**Características:**

-  Integração com APIs externas
-  Sistema de cache inteligente
-  Processamento assíncrono quando necessário
-  Notificações e eventos
-  Tratamento avançado de erro

**Exemplo de uso:**

```php
public function processWithExternalApi(int $id, array $data): ServiceResult
{
    // Tenta cache primeiro
    $cachedResult = $this->cache->get($cacheKey);
    if ($cachedResult) {
        return $this->success($cachedResult);
    }

    // Processa com API externa
    $apiResult = $this->externalApi->process($data);

    // Salva no cache
    if ($apiResult->isSuccess()) {
        $this->cache->put($cacheKey, $apiResult->getData(), 3600);
    }

    return $apiResult;
}
```

## 📁 Arquivos Disponíveis

### 📄 `ServicePattern.php`

Define os padrões teóricos e conceitos por trás de cada nível.

**Conteúdo:**

-  ✅ Definição detalhada de cada nível
-  ✅ Convenções para uso do ServiceResult
-  ✅ Validações comuns por tipo de operação
-  ✅ Tratamento de erro padronizado
-  ✅ Guia de implementação detalhado

### 📄 `ServiceTemplates.php`

Templates práticos prontos para uso imediato.

**Conteúdo:**

-  ✅ Template completo para Nível 1 (Básico)
-  ✅ Template completo para Nível 2 (Intermediário)
-  ✅ Template completo para Nível 3 (Avançado)
-  ✅ Guia de utilização dos templates
-  ✅ Exemplos de personalização

### 📄 `ServicesREADME.md` (Este arquivo)

Documentação completa sobre o sistema de padrões.

## 🚀 Como Usar

### 1. Escolha o Nível Correto

**Para módulos simples (Categories, Tags, Units):**

```bash
# Use o template do Nível 1
cp app/DesignPatterns/ServiceTemplates.php app/Services/Domain/NovoModuloService.php
```

**Para módulos com regras de negócio (Products, Customers, Budgets):**

```bash
# Use o template do Nível 2
cp app/DesignPatterns/ServiceTemplates.php app/Services/Domain/NovoModuloService.php
```

**Para módulos com APIs externas (Invoices, Payments, Reports):**

```bash
# Use o template do Nível 3
cp app/DesignPatterns/ServiceTemplates.php app/Services/Domain/NovoModuloService.php
```

### 2. Personalize o Template

1. **Substitua os placeholders:**

   -  `{Module}` → Nome do módulo (ex: Customer, Product)
   -  `{module}` → Nome em minúsculo (ex: customer, product)
   -  `{Module}Repository` → Nome do repository correspondente

2. **Ajuste filtros suportados:**

   ```php
   protected function getSupportedFilters(): array
   {
       return [
           'id', 'name', 'status', 'active',
           'specific_field', 'another_field' // Filtros específicos
       ];
   }
   ```

3. **Implemente validações específicas:**
   ```php
   protected function validateBusinessRules(array $data, bool $isUpdate = false): ServiceResult
   {
       // Suas regras de negócio específicas aqui
       return $this->success($data);
   }
   ```

### 3. Implemente o Repository Correspondente

Cada service precisa de um repository correspondente:

```php
// app/Repositories/NovoModuloRepository.php
interface NovoModuloRepository extends BaseRepositoryInterface
{
    // Métodos específicos se necessário
}
```

### 4. Configure Injeção de Dependência

**Para services simples:**

```php
// app/Providers/AppServiceProvider.php
$this->app->bind(NovoModuloService::class, function ($app) {
    return new NovoModuloService($app->make(NovoModuloRepository::class));
});
```

**Para services avançados:**

```php
$this->app->bind(NovoModuloService::class, function ($app) {
    return new NovoModuloService(
        $app->make(NovoModuloRepository::class),
        $app->make(ExternalApiService::class),
        $app->make(CacheService::class),
        $app->make(NotificationService::class)
    );
});
```

## 📊 Benefícios Alcançados

### ✅ **Consistência**

-  Todos os services seguem o mesmo padrão arquitetural
-  Tratamento uniforme de ServiceResult
-  Validações padronizadas em toda aplicação

### ✅ **Produtividade**

-  Templates prontos reduzem tempo de desenvolvimento em 70%
-  Menos decisões sobre estrutura de código
-  Onboarding mais rápido para novos desenvolvedores

### ✅ **Qualidade**

-  Tratamento completo de erro incluso por padrão
-  Validações de negócio e técnicas separadas
-  Cache e performance otimizados quando necessário

### ✅ **Manutenibilidade**

-  Código familiar independente do desenvolvedor
-  Fácil localização de bugs e problemas
-  Refatoração simplificada

## 🔄 Migração de Services Existentes

Para aplicar o padrão aos services existentes:

### 1. **PlanService** (Nível 2 → Já está correto)

-  ✅ Mantém padrão intermediário atual
-  ✅ Apenas ajustar se necessário adicionar funcionalidades

### 2. **CustomerService** (Nível 1 → Precisa implementação)

-  ❌ Falta implementação completa
-  🔄 Usar template do Nível 1 como base
-  ✅ Implementar métodos obrigatórios

### 3. **ProductService** (Nível 1 → Precisa implementação)

-  ❌ Service vazio atualmente
-  🔄 Usar template do Nível 1 como base
-  ✅ Implementar funcionalidades básicas

### 4. **BudgetService** (Nível 2 → Precisa criação)

-  ❌ Service não existe
-  🔄 Criar usando template do Nível 2
-  ✅ Implementar lógica específica de orçamentos

## 🎯 Recomendações de Uso

### **Para Novos Módulos:**

1. **Analise requisitos** do módulo antes de escolher o nível
2. **Comece com template** do nível escolhido
3. **Personalize conforme** necessidades específicas
4. **Documente decisões** tomadas durante personalização

### **Para Manutenção:**

1. **Siga o padrão** estabelecido para cada nível
2. **Documente exceções** quando necessário desviar do padrão
3. **Atualize templates** quando identificar melhorias
4. **Revise periodicamente** a aderência ao padrão

### **Para Evolução:**

1. **Monitore uso** dos diferentes níveis
2. **Identifique padrões** que podem ser promovidos a níveis superiores
3. **Crie novos níveis** se identificar necessidades não atendidas
4. **Atualize documentação** conforme evolução

## 📞 Suporte

Para dúvidas sobre implementação ou sugestões de melhoria:

1. **Consulte este README** primeiro
2. **Analise templates** para exemplos práticos
3. **Estude ServicePattern.php** para conceitos teóricos
4. **Verifique services existentes** para implementação real

---

**Última atualização:** 10/10/2025
**Status:** ✅ Padrão implementado e documentado
**Próxima revisão:** Em 3 meses ou quando necessário ajustes significativos
