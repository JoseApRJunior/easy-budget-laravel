# Análise: Uso de withoutGlobalScope() no CategoryService

## 🔍 Problema Identificado

Na linha 105 do `CategoryService.php`, existe uma **inconsistência arquitetural** no uso do `withoutGlobalScope()`:

```php
$query = Category::withoutGlobalScope( \App\Models\Traits\TenantScope::class)
    ->onlyTrashed()
    ->where( 'tenant_id', $tenantId );
```

## ❌ **Problemas Identificados:**

### 1. **Caminho de Classe Incorreto**

-  **Usando:** `\App\Models\Traits\TenantScope::class`
-  **Importado:** `Stancl\Tenancy\Database\TenantScope` (linha 18)
-  **Correto seria:** `TenantScoped::class` ou `TenantScope::class`

### 2. **Inconsistência Arquitetural**

-  O CategoryRepository já implementa `getPaginated()` com funcionalidades específicas
-  Por que criar uma query manual específica para soft delete?
-  Isso quebra a consistência do padrão Repository

### 3. **Duplicação de Lógica**

-  O CategoryRepository já tem `applySoftDeleteFilter()` implementado
-  Por que não usar o mesmo método para manter consistência?

## 🎯 **Por que está sendo usado?**

O `withoutGlobalScope()` está sendo usado para:

1. **Controle específico de Soft Delete:** Aplicar filtros manuais em categorias deletadas
2. **Tenant isolation manual:** Aplicar `where('tenant_id', $tenantId)` explicitamente
3. **Flexibilidade de filtros:** Aplicar filtros normalizados na query manual

## ✅ **Solução Recomendada**

### **Opção 1: Usar CategoryRepository (RECOMENDADA)**

```php
// Modificar CategoryRepository para incluir parâmetro $onlyTrashed
public function getPaginated(array $filters, int $perPage = 10, array $with = [], ?array $orderBy = null, bool $onlyTrashed = false): LengthAwarePaginator
{
    $query = $this->model->newQuery();

    // Aplicar soft delete se solicitado
    if ($onlyTrashed) {
        $query = $this->applySoftDeleteFilter($query, $onlyTrashed);
    }

    // Resto da lógica...
}

// No CategoryService linha 105:
if ($onlyTrashed) {
    // Usar o método do CategoryRepository com parâmetro onlyTrashed
    $paginator = $this->categoryRepository->getPaginated($normalized, $perPage, [], ['name' => 'asc'], true);
} else {
    $paginator = $this->categoryRepository->getPaginated($normalized, $perPage, [], ['name' => 'asc']);
}
```

### **Opção 2: Corrigir o withoutGlobalScope (TEMPORÁRIA)**

```php
// Corrigir a classe correta
$query = Category::withoutGlobalScope( TenantScope::class)
    ->onlyTrashed()
    ->where( 'tenant_id', $tenantId );
```

## 🚀 **Benefícios da Correção**

1. **Consistência Arquitetural:** Usa o mesmo padrão Repository em todo lugar
2. **Reutilização de Código:** Aproveita funcionalidades já implementadas no CategoryRepository
3. **Manutenibilidade:** Centraliza a lógica de paginação em um lugar
4. **Funcionalidades Específicas:** Mantém JOINs hierárquicos e filtros específicos

## 📊 **Impacto da Inconsistência**

| Aspecto              | Status Atual | Impacto                                 |
| -------------------- | ------------ | --------------------------------------- |
| **Consistência**     | ❌ Quebrada  | Queries diferentes para mesmo resultado |
| **Manutenibilidade** | ❌ Difícil   | Lógica duplicada e inconsistente        |
| **Funcionalidades**  | ❌ Limitadas | Filtros hierárquicos perdidos           |
| **Performance**      | ⚠️ Similar   | Pode haver query menos otimizada        |

## 🎯 **Conclusão**

O uso de `withoutGlobalScope()` na linha 105 é um **workaround arquitetural** que deveria ser eliminado em favor da **consistência com o padrão Repository**. A solução ideal é modificar o CategoryRepository para aceitar o parâmetro `$onlyTrashed` e usar esse método consistente em todo lugar.

**Recomendação:** Implementar a **Opção 1** para manter a arquitetura limpa e consistente.
