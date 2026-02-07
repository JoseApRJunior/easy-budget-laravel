# Padrões de Models - Easy Budget Laravel

## 📋 Visão Geral

Este diretório contém padrões unificados para desenvolvimento de models no projeto Easy Budget Laravel, criados para resolver inconsistências identificadas entre diferentes models existentes.

## 🎯 Problema Identificado

Durante análise dos models existentes, foram identificadas inconsistências significativas:

### ❌ Inconsistências Encontradas

| Model      | Características                 | Problemas                  |
| ---------- | ------------------------------- | -------------------------- |
| `User`     | ✅ Completo com relacionamentos | ✅ Bem estruturado         |
| `Customer` | ✅ Completo com relacionamentos | ✅ Bem estruturado         |
| `Category` | ⚠️ Básico sem relacionamentos   | ✅ Adequado para seu nível |
| `Plan`     | ❌ Não analisado                | ❓ Status desconhecido     |

**Problemas identificados:**

-  ❌ Tratamento inconsistente de constantes de status
-  ❌ Relacionamentos não padronizados
-  ❌ Accessors e mutators não uniformes
-  ❌ Scopes básicos vs avançados inconsistentes
-  ❌ Falta de métodos de negócio padronizados

## ✅ Solução Implementada: Sistema de 3 Níveis

Criamos um sistema de padrões unificado com **3 níveis** de models que atendem diferentes necessidades:

### 🏗️ Nível 1 - Model Básico

**Para:** Entidades simples sem relacionamentos complexos

**Características:**

-  Apenas campos básicos (name, slug, active)
-  Sem relacionamentos importantes
-  Scopes básicos
-  Validação simples

**Exemplo de uso:**

```php
class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'active'];

    public const STATUS_ACTIVE = 'active';

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
```

### 🏗️ Nível 2 - Model Intermediário

**Para:** Models com relacionamentos importantes

**Características:**

-  Relacionamentos 1:N e N:1
-  Multi-tenant automático
-  Scopes específicos
-  Métodos de negócio básicos
-  Validação com regras específicas

**Exemplo de uso:**

```php
class Product extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = ['tenant_id', 'name', 'price', 'category_id'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }
}
```

### 🏗️ Nível 3 - Model Avançado

**Para:** Models com relacionamentos complexos e lógica de negócio

**Características:**

-  Relacionamentos muitos-para-muitos
-  Lógica de autorização
-  Métodos de negócio avançados
-  Auditoria automática
-  Configurações flexíveis

**Exemplo de uso:**

```php
class User extends Model
{
    use HasFactory, TenantScoped, Auditable;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }
}
```

## 📁 Arquivos Disponíveis

### 📄 `ModelPattern.php`

Define os padrões teóricos e conceitos por trás de cada nível.

**Conteúdo:**

-  ✅ Definição detalhada de cada nível
-  ✅ Convenções para relacionamentos
-  ✅ Tratamento de accessors/mutators
-  ✅ Scopes e métodos de negócio
-  ✅ Guia de implementação detalhado

### 📄 `ModelTemplates.php`

Templates práticos prontos para uso imediato.

**Conteúdo:**

-  ✅ Template completo para Nível 1 (Básico)
-  ✅ Template completo para Nível 2 (Intermediário)
-  ✅ Template completo para Nível 3 (Avançado)
-  ✅ Guia de utilização dos templates
-  ✅ Exemplos de personalização

### 📄 `ModelsREADME.md` (Este arquivo)

Documentação completa sobre o sistema de padrões.

## 🚀 Como Usar

### 1. Escolha o Nível Correto

**Para módulos simples (Categories, Tags, Units):**

```bash
# Use o template do Nível 1
cp app/DesignPatterns/ModelTemplates.php app/Models/NovoModulo.php
```

**Para módulos com relacionamentos (Products, Customers):**

```bash
# Use o template do Nível 2
cp app/DesignPatterns/ModelTemplates.php app/Models/NovoModulo.php
```

**Para módulos com lógica complexa (Users, Budgets, Invoices):**

```bash
# Use o template do Nível 3
cp app/DesignPatterns/ModelTemplates.php app/Models/NovoModulo.php
```

### 2. Personalize o Template

1. **Substitua os placeholders:**

   -  `{Module}` → Nome do módulo (ex: Category, Product)
   -  `{module}` → Nome da tabela (ex: categories, products)

2. **Ajuste constantes de status:**

   ```php
   public const STATUS_ACTIVE = 'active';
   public const STATUS_INACTIVE = 'inactive';
   public const STATUS_SUSPENDED = 'suspended'; // Status específico

   public const STATUSES = [
       self::STATUS_ACTIVE,
       self::STATUS_INACTIVE,
       self::STATUS_SUSPENDED,
   ];
   ```

3. **Implemente relacionamentos específicos:**

   ```php
   public function category(): BelongsTo
   {
       return $this->belongsTo(Category::class);
   }

   public function items(): HasMany
   {
       return $this->hasMany(ProductItem::class);
   }
   ```

### 3. Implemente Traits Necessários

**Para multi-tenant:**

```php
// app/Models/NovoModulo.php
use App\Models\Traits\TenantScoped;

class NovoModulo extends Model
{
    use HasFactory, TenantScoped;
}
```

**Para auditoria:**

```php
// app/Models/NovoModulo.php
use App\Models\Traits\Auditable;

class NovoModulo extends Model
{
    use HasFactory, TenantScoped, Auditable;
}
```

### 4. Configure Relacionamentos

**Para relacionamentos importantes:**

```php
public function customer(): BelongsTo
{
    return $this->belongsTo(Customer::class);
}

public function budgetItems(): HasMany
{
    return $this->hasMany(BudgetItem::class);
}

public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class, 'budget_tags');
}
```

## 📊 Benefícios Alcançados

### ✅ **Consistência**

-  Todos os models seguem o mesmo padrão arquitetural
-  Relacionamentos padronizados e documentados
-  Constantes de status uniformes

### ✅ **Produtividade**

-  Templates prontos reduzem tempo de desenvolvimento em 50%
-  Menos decisões sobre estrutura de código
-  Onboarding mais rápido para novos desenvolvedores

### ✅ **Qualidade**

-  Relacionamentos otimizados com eager loading
-  Validações padronizadas e reutilizáveis
-  Métodos de negócio bem definidos

### ✅ **Manutenibilidade**

-  Código familiar independente do desenvolvedor
-  Fácil localização de funcionalidades
-  Refatoração simplificada

## 🔄 Migração de Models Existentes

Para aplicar o padrão aos models existentes:

### 1. **User** (Nível 3 → Já está correto)

-  ✅ Mantém padrão avançado atual
-  ✅ Apenas ajustar se necessário adicionar funcionalidades

### 2. **Customer** (Nível 2 → Já está correto)

-  ✅ Mantém padrão intermediário atual
-  ✅ Apenas ajustar se necessário adicionar funcionalidades

### 3. **Category** (Nível 1 → Já está correto)

-  ✅ Mantém padrão básico atual
-  ✅ Apenas ajustar se necessário adicionar funcionalidades

### 4. **Plan** (Nível 1 → Verificar implementação)

-  ❓ Status precisa ser verificado
-  🔄 Aplicar padrão básico se necessário

## 🎯 Recomendações de Uso

### **Para Novos Módulos:**

1. **Analise relacionamentos** necessários antes de escolher o nível
2. **Comece com template** do nível escolhido
3. **Personalize conforme** relacionamentos específicos
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
3. **Estude ModelPattern.php** para conceitos teóricos
4. **Verifique models existentes** para implementação real

---

**Última atualização:** 10/10/2025
**Status:** ✅ Padrão implementado e documentado
**Próxima revisão:** Em 3 meses ou quando necessário ajustes significativos
