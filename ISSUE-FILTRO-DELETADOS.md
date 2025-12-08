# 🐛 Issue: Filtro de Status Ignorado ao Buscar Deletados

## Problema
Quando o usuário seleciona:
- Status: **Inativo**
- Registros: **Deletados**

O sistema ignora o filtro de status e mostra todos os registros deletados (ativos e inativos).

## Causa
No `CategoryController::index()`, quando `deleted = 'only'`, o método chama:
- `$service->paginateOnlyTrashed($serviceFilters, $perPage)`

Porém, o método `paginateOnlyTrashed` no `CategoryService` provavelmente não está aplicando o filtro `active` que está em `$serviceFilters`.

## Solução

### Opção 1: Corrigir no CategoryService
Editar o método `paginateOnlyTrashed` em `App\Services\Domain\CategoryService` para aplicar o filtro `active`:

```php
public function paginateOnlyTrashed(array $filters, int $perPage)
{
    $query = Category::onlyTrashed();
    
    // Aplicar filtro de busca
    if (!empty($filters['search'])) {
        $query->where(function($q) use ($filters) {
            $q->where('name', 'like', "%{$filters['search']}%")
              ->orWhere('slug', 'like', "%{$filters['search']}%");
        });
    }
    
    // ADICIONAR: Aplicar filtro de status
    if (isset($filters['active']) && $filters['active'] !== '') {
        $query->where('is_active', $filters['active'] === '1');
    }
    
    return $query->paginate($perPage);
}
```

### Opção 2: Corrigir no Controller (Workaround)
Se não puder editar o Service, adicione o filtro após buscar:

```php
// No CategoryController::index(), após buscar deletados:
if (isset($filters['active']) && $filters['active'] !== '') {
    $categories = $categories->filter(function($cat) use ($filters) {
        return $cat->is_active == ($filters['active'] === '1');
    });
}
```

## Arquivos Afetados
- `app/Http/Controllers/CategoryController.php` (linha ~90-130)
- `app/Services/Domain/CategoryService.php` (método `paginateOnlyTrashed`)
- `app/Services/Domain/CategoryService.php` (método `paginateOnlyTrashedForTenant`)

## Teste
1. Criar categoria inativa
2. Deletar a categoria
3. Filtrar por: Status = Inativo + Registros = Deletados
4. Verificar se mostra apenas a categoria inativa deletada

---
**Data:** <?php echo date('d/m/Y H:i'); ?>
**Prioridade:** Média
**Status:** Pendente
