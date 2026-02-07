# Guia de Uso - Componentes Reutilizáveis

Este documento contém exemplos práticos de uso dos componentes Blade criados para padronizar as tabelas da aplicação.

---

## 📦 Componentes Disponíveis

1. ✅ `action-buttons.blade.php` - Botões de ação da tabela
2. ✅ `table-header-actions.blade.php` - Exportar + Nova
3. ✅ `status-badge.blade.php` - Badge de status
4. ✅ `confirm-modal.blade.php` - Modais de confirmação
5. ✅ `empty-state.blade.php` - Estado vazio
6. ✅ `filter-form.blade.php` - Formulário de filtros
7. ✅ `filter-field.blade.php` - Campos de filtro individuais

**Localização**: `resources/views/components/`

---

## 1. Action Buttons

### Props
- `item` - O modelo/objeto (obrigatório)
- `resource` - Nome do recurso no plural (obrigatório)
- `identifier` - Campo identificador (padrão: 'id')
- `nameField` - Campo do nome (padrão: 'name')
- `canDelete` - Se pode deletar (padrão: true)
- `restoreBlocked` - Se restauração está bloqueada (padrão: false)
- `restoreBlockedMessage` - Mensagem quando bloqueado
- `size` - Tamanho dos botões (null, 'sm', 'lg')

### Uso Básico
```blade
{{-- Desktop table --}}
<td>
    <x-action-buttons
        :item="$category"
        resource="categories"
        identifier="slug"
    />
</td>

{{-- Mobile list --}}
<div class="d-flex gap-2">
    <x-action-buttons
        :item="$product"
        resource="products"
        identifier="sku"
        size="sm"
    />
</div>
```

### Uso Avançado
```blade
<x-action-buttons
    :item="$category"
    resource="categories"
    identifier="slug"
    :canDelete="$category->children_count === 0 && $category->services_count === 0"
    :restoreBlocked="$category->parent_id && $category->parent?->trashed()"
    restoreBlockedMessage="Não é possível restaurar esta subcategoria porque a categoria pai está na lixeira."
/>
```

---

## 2. Table Header Actions

### Props
- `resource` - Nome do recurso (obrigatório)
- `exportFormats` - Array de formatos (padrão: ['xlsx', 'pdf'])
- `filters` - Array de filtros atuais (padrão: [])
- `createRoute` - Rota customizada de criar (opcional)
- `createLabel` - Label do botão criar (padrão: 'Novo')
- `size` - Tamanho dos botões (padrão: 'sm')
- `showExport` - Mostrar exportação (padrão: true)
- `showCreate` - Mostrar botão criar (padrão: true)

### Uso Básico
```blade
<x-table-header-actions
    resource="categories"
    :filters="$filters"
    createLabel="Nova"
/>
```

### Com formatos customizados
```blade
<x-table-header-actions
    resource="products"
    :exportFormats="['xlsx', 'pdf', 'csv']"
    :filters="$filters"
    createLabel="Novo Produto"
/>
```

### Com ações adicionais
```blade
<x-table-header-actions resource="budgets" :filters="$filters">
    <x-button type="link" href="{{ route('provider.budgets.archived') }}"
        size="sm" icon="archive" label="Arquivados" variant="outline-info" />
</x-table-header-actions>
```

---

## 3. Status Badge

### Props
- `item` - O modelo/objeto (obrigatório)
- `statusField` - Campo de status (padrão: 'is_active')
- `activeLabel` - Label ativo (padrão: 'Ativo')
- `inactiveLabel` - Label inativo (padrão: 'Inativo')
- `deletedLabel` - Label deletado (padrão: 'Deletado')

### Uso Básico
```blade
<td>
    <x-status-badge :item="$category" />
</td>
```

### Com campo customizado
```blade
{{-- Para produto que usa 'active' ao invés de 'is_active' --}}
<x-status-badge :item="$product" statusField="active" />
```

### Com labels customizados
```blade
<x-status-badge
    :item="$invoice"
    statusField="is_paid"
    activeLabel="Pago"
    inactiveLabel="Pendente"
/>
```

---

## 4. Confirm Modal

### Props
- `id` - ID do modal (obrigatório)
- `type` - Tipo: 'delete', 'restore', 'confirm' (padrão: 'delete')
- `resource` - Nome do recurso no singular (padrão: 'item')
- `method` - Método HTTP (padrão: 'DELETE')
- `title`, `message`, `confirmLabel` - Customizações (opcional)

### Uso Básico
```blade
{{-- No final da view, antes de @endsection --}}
<x-confirm-modal id="deleteModal" type="delete" resource="categoria" method="DELETE" />
<x-confirm-modal id="restoreModal" type="restore" resource="categoria" method="POST" />
```

### Uso Customizado
```blade
<x-confirm-modal
    id="archiveModal"
    type="confirm"
    resource="orçamento"
    method="POST"
    title="Arquivar Orçamento"
    message="Deseja arquivar o orçamento <strong id='archiveModalItemName'></strong>?"
    confirmLabel="Arquivar"
/>
```

> **Nota**: O componente `action-buttons` já configura automaticamente os data attributes necessários para os modais funcionarem.

---

## 5. Empty State

### Props
- `icon` - Ícone do Bootstrap Icons (padrão: 'inbox')
- `resource` - Nome do recurso no plural (padrão: 'item')
- `isTrashView` - Se é visualização de lixeira (padrão: false)
- `isSearchView` - Se é resultado de busca (padrão: false)
- `message`, `submessage` - Mensagens customizadas (opcional)
- `iconSize` - Tamanho do ícone (padrão: '2rem')

### Desktop Table
```blade
@forelse($categories as $category)
    <tr>
        {{-- conteúdo --}}
    </tr>
@empty
    <tr>
        <td colspan="6" class="text-center">
            <x-empty-state
                resource="categorias"
                :isTrashView="($filters['deleted'] ?? '') === 'only'"
            />
        </td>
    </tr>
@endforelse
```

### Mobile List
```blade
@forelse($products as $product)
    <div class="list-group-item">
        {{-- conteúdo --}}
    </div>
@empty
    <x-empty-state
        icon="box-seam"
        resource="produtos"
        :isTrashView="($filters['deleted'] ?? '') === 'only'"
    />
@endforelse
```

### Com mensagem customizada
```blade
<x-empty-state
    icon="calendar"
    resource="eventos"
    message="Nenhum evento programado."
    submessage="Crie seu primeiro evento clicando no botão acima."
/>
```

---

## 6. Filter Form

### Props
- `id` - ID do formulário (obrigatório)
- `route` - Rota do formulário (obrigatório)
- `filters` - Array de filtros atuais (padrão: [])
- `title` - Título do card (padrão: 'Filtros de Busca')
- `icon` - Ícone do título (padrão: 'filter')
- `submitLabel` - Label do botão (padrão: 'Filtrar')

### Uso Básico
```blade
<x-filter-form
    id="filtersFormCategories"
    :route="route('provider.categories.index')"
    :filters="$filters"
>
    <x-filter-field type="text" name="search" label="Buscar"
        placeholder="Categoria, Subcategoria" :filters="$filters" />

    <x-filter-field type="select" name="active" label="Status" col="col-md-2"
        :options="['1' => 'Ativo', '0' => 'Inativo', 'all' => 'Todos']" :filters="$filters" />

    <x-filter-field type="date" name="start_date" label="Data Inicial"
        col="col-md-2" :filters="$filters" />
</x-filter-form>
```

---

## 7. Filter Field

### Props
- `type` - Tipo: 'text', 'select', 'date', 'textarea' (padrão: 'text')
- `name` - Nome do campo (obrigatório)
- `label` - Label do campo (obrigatório)
- `filters` - Array de filtros (para valor automático)
- `placeholder` - Placeholder
- `options` - Opções para select
- `col` - Classes de coluna (padrão: 'col-md-4')
- `required` - Campo obrigatório (padrão: false)

### Campo de Texto
```blade
<x-filter-field
    type="text"
    name="search"
    label="Buscar"
    placeholder="Nome ou SKU"
    col="col-md-6"
    :filters="$filters"
/>
```

### Select
```blade
<x-filter-field
    type="select"
    name="category"
    label="Categoria"
    col="col-md-3"
    :options="['' => 'Todas', 'electronics' => 'Eletrônicos']"
    :filters="$filters"
/>
```

### Data
```blade
<x-filter-field
    type="date"
    name="start_date"
    label="Data Inicial"
    col="col-md-2"
    :filters="$filters"
/>
```

---

## 🎯 Exemplo Completo

```blade
@extends('layouts.app')

@section('content')
<div class="container-fluid py-1">
    <x-page-header title="Categorias" icon="tags" ...>
        <p class="text-muted mb-0">Lista de suas categorias</p>
    </x-page-header>

    {{-- Filtros --}}
    <x-filter-form id="filtersFormCategories" :route="route('provider.categories.index')" :filters="$filters">
        <x-filter-field type="text" name="search" label="Buscar" :filters="$filters" />
        <x-filter-field type="select" name="active" label="Status" col="col-md-2"
            :options="['1' => 'Ativo', '0' => 'Inativo']" :filters="$filters" />
    </x-filter-form>

    {{-- Tabela --}}
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-12 col-lg-8">
                    <h5>Lista de Categorias ({{ $categories->total() }})</h5>
                </div>
                <x-table-header-actions resource="categories" :filters="$filters" createLabel="Nova" />
            </div>
        </div>

        <div class="card-body p-0">
            <table class="modern-table table mb-0">
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td><x-status-badge :item="$category" /></td>
                        <td>
                            <x-action-buttons
                                :item="$category"
                                resource="categories"
                                identifier="slug"
                                :canDelete="$category->children_count === 0"
                            />
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3">
                            <x-empty-state resource="categorias" :isTrashView="false" />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modais --}}
<x-confirm-modal id="deleteModal" type="delete" resource="categoria" method="DELETE" />
<x-confirm-modal id="restoreModal" type="restore" resource="categoria" method="POST" />

@endsection
```

---

## 💡 Dicas de Uso

1. **Sempre passe `:filters="$filters"`** para os componentes de filtro
2. **Use `identifier="slug"`** para recursos que usam slug ao invés de id
3. **Combine múltiplos componentes** para máxima reutilização
4. **Use slots** quando precisar customizar além das props
5. **Mantenha os componentes genéricos** - evite lógica específica de negócio

---

## 🔧 Classes CSS Necessárias

Os componentes usam as seguintes classes que devem estar definidas no CSS:

```css
.modern-badge
.badge-active
.badge-inactive
.badge-deleted
.action-btn-group
.item-icon
.modern-table
```

---

**Versão**: 1.0
**Última atualização**: 31/12/2024
