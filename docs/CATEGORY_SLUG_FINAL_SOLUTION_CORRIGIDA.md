# Solução Final Corrigida para Slugs de Categorias Multi-Tenant

## Análise dos Problemas Atuais

### Problemas Identificados na Implementação Atual

1. **Estrutura de Banco de Dados Inconsistente**:

   -  A migration adiciona campos `is_custom`, `is_default` e `tenant_id` na tabela `categories`
   -  Mas a tabela `category_tenant` também tem campos `is_custom` e `is_default` duplicados
   -  Isso cria redundância e complexidade desnecessária

2. **Lógica de Validação Incompleta**:

   -  O método `generateUniqueSlug` no `CategoryService` não considera o contexto do tenant
   -  O método `validateUniqueSlug` no `Category` não recebe o parâmetro `$tenantId`
   -  A validação de unicidade não está sendo aplicada corretamente

3. **Inconsistência nos Métodos de Modelo**:

   -  O método `isGlobal()` verifica a tabela `tenants` ao invés do campo `tenant_id`
   -  O método `isCustomFor()` também verifica a tabela `tenants` ao invés do campo `tenant_id`
   -  Isso causa problemas porque categorias globais podem ter `tenant_id = null`

4. **Problemas no Service**:
   -  O método `createCategory` não recebe o parâmetro `$tenantId`
   -  A lógica de criação de categorias custom não está implementada corretamente

## Solução Proposta (Corrigida)

### 1. Estrutura de Banco de Dados Simplificada

#### Tabela `categories`

```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) NOT NULL,  -- Único globalmente para categorias globais
    name VARCHAR(255) NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_custom BOOLEAN DEFAULT FALSE,  -- Indica se é categoria custom
    tenant_id BIGINT UNSIGNED NULL,    -- Tenant proprietário (para categorias custom)
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uq_categories_slug_global (slug)  -- Único para categorias globais
);
```

#### Tabela `category_tenant` (Simplificada)

```sql
CREATE TABLE category_tenant (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    tenant_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uq_category_tenant (category_id, tenant_id)
);
```

### 2. Lógica de Validação e Geração de Slugs (Corrigida)

#### Validação de Slugs

**Para Categorias Globais:**

-  Slug deve ser único globalmente
-  Validação: `Category::where('slug', $slug)->whereNull('tenant_id')->exists()`

**Para Categorias Customizadas:**

-  Slug deve ser único apenas dentro do tenant
-  Validação: `Category::where('slug', $slug)->where('tenant_id', $tenantId)->exists()`

#### Geração de Slugs (Corrigida)

```php
public function generateUniqueSlug(string $name, ?int $tenantId = null, ?int $excludeId = null): string
{
    $base = Str::slug($name);
    $slug = $base;
    $i = 1;

    while ($this->categoryRepository->existsBySlug($slug, $tenantId, $excludeId)) {
        $slug = $base . '-' . $i;
        $i++;
    }

    return $slug;
}
```

### 3. Implementação no Modelo Category (Corrigida)

#### Método `validateUniqueSlug` (Corrigido)

```php
public static function validateUniqueSlug(string $slug, ?int $tenantId = null, ?int $excludeCategoryId = null): bool
{
    $query = static::where('slug', $slug);

    // Se tenantId for fornecido, verificar apenas no contexto do tenant
    if ($tenantId !== null) {
        $query->where('tenant_id', $tenantId);
    } else {
        // Para categorias globais, verificar apenas categorias sem tenant_id
        $query->whereNull('tenant_id');
    }

    // Se excludeCategoryId for fornecido, ignorar a categoria com esse ID
    if ($excludeCategoryId !== null) {
        $query->where('id', '!=', $excludeCategoryId);
    }

    return !$query->exists();
}
```

#### Método `isGlobal` (Corrigido)

```php
public function isGlobal(): bool
{
    // Categorias globais não têm tenant_id ou têm is_custom = false
    return $this->tenant_id === null || !$this->is_custom;
}
```

#### Método `isCustomFor` (Corrigido)

```php
public function isCustomFor(int $tenantId): bool
{
    // Categorias custom têm tenant_id e is_custom = true
    return $this->tenant_id === $tenantId && $this->is_custom;
}
```

### 4. Implementação no Service (Corrigida)

#### Método `createCategory` (Corrigido)

```php
public function createCategory(array $data, ?int $tenantId = null): ServiceResult
{
    try {
        return DB::transaction(function () use ($data, $tenantId) {
            // Gerar slug único considerando o contexto
            if (!isset($data['slug']) || empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $tenantId);
            }

            // Validar slug único
            if (!Category::validateUniqueSlug($data['slug'], $tenantId)) {
                return ServiceResult::error(
                    OperationStatus::INVALID_DATA,
                    'Slug já existe para este contexto',
                    null,
                    new Exception('Slug duplicado')
                );
            }

            // Criar categoria
            $category = Category::create([
                'slug' => $data['slug'],
                'name' => $data['name'],
                'parent_id' => $data['parent_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_custom' => $tenantId !== null,
                'tenant_id' => $tenantId,
            ]);

            // Se for categoria custom, vincular ao tenant na tabela pivot
            if ($tenantId !== null) {
                $category->tenants()->attach($tenantId);
            }

            return ServiceResult::success($category, 'Categoria criada com sucesso');
        });
    } catch (\Exception $e) {
        return ServiceResult::error(OperationStatus::ERROR, 'Erro ao criar categoria: ' . $e->getMessage(), null, $e);
    }
}
```

#### Método `updateCategory` (Corrigido)

```php
public function updateCategory(int $id, array $data): ServiceResult
{
    try {
        $categoryResult = $this->findById($id);
        if ($categoryResult->isError()) {
            return $categoryResult;
        }

        $category = $categoryResult->getData();
        $tenantId = $category->tenant_id;

        // Se o nome foi alterado e slug não foi fornecido, gerar novo slug
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $tenantId, $id);
        }

        // Validar slug único
        if (isset($data['slug']) && !Category::validateUniqueSlug($data['slug'], $tenantId, $id)) {
            return ServiceResult::error(
                OperationStatus::INVALID_DATA,
                'Slug já existe para este contexto',
                null,
                new Exception('Slug duplicado')
            );
        }

        return $this->update($id, $data);
    } catch (\Exception $e) {
        return ServiceResult::error(OperationStatus::ERROR, 'Erro ao atualizar categoria: ' . $e->getMessage(), null, $e);
    }
}
```

### 5. Implementação no Repository (Corrigida)

#### Método `existsBySlug` (Corrigido)

```php
public function existsBySlug(string $slug, ?int $tenantId = null, ?int $excludeId = null): bool
{
    $query = $this->model->where('slug', $slug);

    // Se tenantId for fornecido, verificar apenas no contexto do tenant
    if ($tenantId !== null) {
        $query->where('tenant_id', $tenantId);
    } else {
        // Para categorias globais, verificar apenas categorias sem tenant_id
        $query->whereNull('tenant_id');
    }

    // Se excludeId for fornecido, ignorar a categoria com esse ID
    if ($excludeId !== null) {
        $query->where('id', '!=', $excludeId);
    }

    return $query->exists();
}
```

## Fluxos de Negócio (Corrigidos)

### Fluxo de Criação de Categoria Global

1. Admin global acessa interface de categorias globais
2. Preenche nome e outros campos
3. Sistema gera slug único globalmente
4. Categoria é criada com `tenant_id = null` e `is_custom = false`
5. Categoria é visível para todos os tenants

### Fluxo de Criação de Categoria Custom

1. Provider (tenant) acessa interface de categorias custom
2. Preenche nome e outros campos
3. Sistema gera slug único dentro do contexto do tenant
4. Categoria é criada com `tenant_id = [tenant_id]` e `is_custom = true`
5. Categoria é vinculada ao tenant na tabela `category_tenant`
6. Categoria é visível apenas para o tenant proprietário

### Fluxo de Atualização de Categoria

1. Usuário acessa categoria para edição
2. Sistema verifica se é categoria global ou custom
3. Se slug for alterado:
   -  Para categorias globais: valida unicidade global (tenant_id = null)
   -  Para categorias custom: valida unicidade dentro do tenant
4. Categoria é atualizada com novos dados

## Benefícios da Solução Corrigida

1. **Simplicidade**: Estrutura mais simples e fácil de entender
2. **Flexibilidade**: Cada tenant pode ter suas próprias categorias com nomes/slugs iguais
3. **Isolamento**: Categorias de diferentes tenants não interferem entre si
4. **Escalabilidade**: Permite que tenants tenham estruturas de categorias independentes
5. **Performance**: Consultas otimizadas com filtros adequados
6. **Consistência**: Lógica clara e coerente em todas as camadas

## Considerações de Segurança

1. **Validação de Permissões**:

   -  Admin global pode gerenciar apenas categorias globais
   -  Providers podem gerenciar apenas categorias custom do seu tenant
   -  Validação deve ser feita em todos os controllers e services

2. **Injeção de SQL**:

   -  Usar sempre Eloquent queries em vez de raw queries
   -  Validar todos os inputs antes de usar em consultas

3. **Autorização**:
   -  Implementar políticas (Policies) para validação de permissões
   -  Usar gates e policies do Laravel para controle de acesso

## Testes Recomendados

1. **Testes de Unicidade**:

   -  Verificar que slugs globais são únicos globalmente
   -  Verificar que slugs custom são únicos por tenant
   -  Testar cenários de slugs duplicados entre tenants diferentes

2. **Testes de Permissões**:

   -  Verificar que admin global não pode editar categorias custom
   -  Verificar que providers não podem ver categorias de outros tenants
   -  Verificar que providers não podem editar categorias globais

3. **Testes de Performance**:
   -  Verificar performance de consultas com grandes volumes de dados
   -  Testar consultas com filtros por tenant
   -  Verificar impacto de índices na performance

## Conclusão

A solução proposta atende a todos os requisitos de slugs únicos para categorias globais e customizadas, proporcionando flexibilidade, isolamento e escalabilidade para o sistema multi-tenant. A implementação segue os padrões do Laravel e utiliza as melhores práticas de desenvolvimento, garantindo manutenibilidade e performance.

**Próximos Passos:**

1. Implementar os métodos propostos nos modelos e services
2. Atualizar factories e seeders para refletir a nova lógica
3. Criar testes abrangentes para validar a solução
4. Documentar a nova estrutura para a equipe de desenvolvimento e suporte
5. Corrigir a migration para remover campos duplicados da tabela `category_tenant`
