# Documento de Referência Cruzada - Skills e Components

## Visão Geral

Este documento estabelece a relação entre as skills existentes e os componentes reutilizáveis do sistema, garantindo alinhamento com o novo padrão de components.

## Estrutura de Views Atual

O sistema utiliza uma arquitetura de views baseada em:

1. **Components** (`resources/views/components/`): Componentes reutilizáveis e atômicos
2. **Pages** (`resources/views/pages/`): Páginas completas que utilizam components
3. **Partials** (`resources/views/partials/`): Seções reutilizáveis e componentes complexos

## Skills Existentes e Seu Relacionamento com Components

### 1. budget-lifecycle-rules

**Foco:** Lógica de negócio e serviços para gestão de orçamentos

**Components Relacionados:**
- `components/status-badge.blade.php` - Para exibição de status de orçamento
- `components/stat-card.blade.php` - Para métricas de orçamentos
- `components/table-actions.blade.php` - Para ações em tabelas de orçamentos
- `components/modal.blade.php` - Para confirmações e diálogos

**Atualização Recomendada:**
- Adicionar referências aos componentes acima na documentação
- Incluir exemplos de como usar os componentes nas views de orçamento
- Referenciar a skill `reusable-components` para padrões de uso

### 2. service-lifecycle

**Foco:** Lógica de negócio e serviços para gestão de serviços

**Components Relacionados:**
- `components/status-badge.blade.php` - Para exibição de status de serviço
- `components/stat-card.blade.php` - Para métricas de serviços
- `components/table-actions.blade.php` - Para ações em tabelas de serviços
- `components/movement-type-badge.blade.php` - Para tipos de movimentação

**Atualização Recomendada:**
- Adicionar referências aos componentes acima na documentação
- Incluir exemplos de como usar os componentes nas views de serviços
- Referenciar a skill `service-ui-components` para componentes específicos de serviços

### 3. invoice-management

**Foco:** Lógica de negócio e serviços para gestão de faturas

**Components Relacionados:**
- `components/status-badge.blade.php` - Para exibição de status de fatura
- `components/stat-card.blade.php` - Para métricas de faturas
- `components/table-actions.blade.php` - Para ações em tabelas de faturas
- `components/pdf/*` - Para geração de PDFs de faturas

**Atualização Recomendada:**
- Adicionar referências aos componentes acima na documentação
- Incluir exemplos de como usar os componentes nas views de faturas
- Referenciar a skill `invoice-ui-components` para componentes específicos de faturas

### 4. customer-management

**Foco:** Lógica de negócio e serviços para gestão de clientes

**Components Relacionados:**
- `components/resource-list-card.blade.php` - Para listagem de clientes
- `components/resource-mobile-item.blade.php` - Para exibição mobile de clientes
- `components/table-actions.blade.php` - Para ações em tabelas de clientes
- `components/empty-state.blade.php` - Para estados vazios

**Atualização Recomendada:**
- Adicionar referências aos componentes acima na documentação
- Incluir exemplos de como usar os componentes nas views de clientes
- Referenciar a skill `customer-ui-components` para componentes específicos de clientes

### 5. product-management

**Foco:** Lógica de negócio e serviços para gestão de produtos

**Components Relacionados:**
- `components/product-info.blade.php` - Para exibição de informações de produtos
- `components/resource-list-card.blade.php` - Para listagem de produtos
- `components/table-actions.blade.php` - Para ações em tabelas de produtos
- `components/stat-card.blade.php` - Para métricas de produtos

**Atualização Recomendada:**
- Adicionar referências aos componentes acima na documentação
- Incluir exemplos de como usar os componentes nas views de produtos
- Referenciar a skill `product-ui-components` para componentes específicos de produtos

### 6. report-generation

**Foco:** Lógica de negócio e serviços para geração de relatórios

**Components Relacionados:**
- `components/filter-form.blade.php` - Para formulários de filtro
- `components/filter-field.blade.php` - Para campos de filtro
- `components/stat-card.blade.php` - Para exibição de métricas
- `components/empty-state.blade.php` - Para estados vazios

**Atualização Recomendada:**
- Adicionar referências aos componentes acima na documentação
- Incluir exemplos de como usar os componentes nas views de relatórios
- Referenciar a skill `report-ui-components` para componentes específicos de relatórios

## Relações entre Skills

### Skills de UI e suas Relações

1. **reusable-components**: Skill base que define componentes reutilizáveis comuns
   - Relacionada com todas as outras skills de UI
   - Fornece componentes básicos como botões, modais, cards, etc.

2. **layout-navigation-components**: Skill para componentes de layout e navegação
   - Relacionada com todas as skills que utilizam layout
   - Fornece componentes de navegação, cabeçalhos, rodapés, etc.

3. **budget-ui-components**: Skill para componentes específicos de orçamentos
   - Relacionada com `budget-lifecycle-rules`
   - Fornece componentes específicos para gestão de orçamentos

4. **customer-ui-components**: Skill para componentes específicos de clientes
   - Relacionada com `customer-management`
   - Fornece componentes específicos para gestão de clientes

5. **product-ui-components**: Skill para componentes específicos de produtos
   - Relacionada com `product-management`
   - Fornece componentes específicos para gestão de produtos

6. **service-ui-components**: Skill para componentes específicos de serviços
   - Relacionada com `service-lifecycle`
   - Fornece componentes específicos para gestão de serviços

7. **invoice-ui-components**: Skill para componentes específicos de faturas
   - Relacionada com `invoice-management`
   - Fornece componentes específicos para gestão de faturas

8. **report-ui-components**: Skill para componentes específicos de relatórios
   - Relacionada com `report-generation`
   - Fornece componentes específicos para geração de relatórios

## Componentes Reutilizáveis entre Módulos

### Componentes Comuns (reusable-components)

1. **Botões e Ações**
   - `components/button.blade.php`
   - `components/danger-button.blade.php`
   - `components/primary-button.blade.php`
   - `components/secondary-button.blade.php`
   - `components/action-buttons.blade.php`

2. **Modais e Diálogos**
   - `components/modal.blade.php`
   - `components/confirm-modal.blade.php`

3. **Cards e Indicadores**
   - `components/stat-card.blade.php`
   - `components/status-badge.blade.php`
   - `components/status-description.blade.php`

4. **Tabelas e Listagens**
   - `components/resource-table.blade.php`
   - `components/table-actions.blade.php`
   - `components/table-header-actions.blade.php`

5. **Filtros e Formulários**
   - `components/filter-form.blade.php`
   - `components/filter-field.blade.php`

### Componentes Específicos por Módulo

1. **Orçamentos (budget-ui-components)**
   - Componentes específicos para gestão de orçamentos
   - Relacionados com `budget-lifecycle-rules`

2. **Clientes (customer-ui-components)**
   - Componentes específicos para gestão de clientes
   - Relacionados com `customer-management`

3. **Produtos (product-ui-components)**
   - Componentes específicos para gestão de produtos
   - Relacionados com `product-management`

4. **Serviços (service-ui-components)**
   - Componentes específicos para gestão de serviços
   - Relacionados com `service-lifecycle`

5. **Faturas (invoice-ui-components)**
   - Componentes específicos para gestão de faturas
   - Relacionados com `invoice-management`

6. **Relatórios (report-ui-components)**
   - Componentes específicos para geração de relatórios
   - Relacionados com `report-generation`

## Recomendações para Atualização das Skills

### 1. Adicionar Seção de Components

Cada skill deve incluir uma seção "Components Relacionados" que:
- Liste os componentes específicos do módulo
- Referencie componentes comuns reutilizáveis
- Inclua exemplos de uso

### 2. Atualizar Exemplos de Implementação

Os exemplos de implementação devem:
- Mostrar como usar components nas views
- Demonstrar a composição de páginas usando components
- Incluir exemplos de props e slots

### 3. Referências Cruzadas

Cada skill deve referenciar:
- Skills relacionadas (ex: budget-lifecycle-rules → budget-ui-components)
- Componentes reutilizáveis (ex: reusable-components)
- Padrões de implementação (ex: layout-navigation-components)

### 4. Documentação de Uso

Incluir documentação sobre:
- Quando usar components vs pages
- Como criar novos components
- Como estender components existentes
- Padrões de nomenclatura

## Exemplo de Atualização para Skill

```markdown
# Budget Lifecycle Rules

## Components Relacionados

Esta skill utiliza os seguintes componentes:

### Componentes Específicos
- `budget-ui-components`: Componentes específicos para orçamentos
- `components/status-badge.blade.php`: Para exibição de status
- `components/stat-card.blade.php`: Para métricas

### Componentes Reutilizáveis
- `reusable-components`: Componentes comuns do sistema
- `components/modal.blade.php`: Para diálogos de confirmação
- `components/table-actions.blade.php`: Para ações em tabelas

## Exemplos de Implementação

### Exemplo 1: Listagem de Orçamentos

```php
// Usando componentes na view
<x-page-container>
    <x-page-header title="Orçamentos" />

    <x-filter-form>
        <x-filter-field name="status" label="Status" />
        <x-filter-field name="date_range" label="Período" />
    </x-filter-form>

    <x-resource-table :resources="$budgets">
        <x-slot name="header">
            <x-table-header-actions>
                <x-primary-button href="{{ route('budgets.create') }}">
                    Novo Orçamento
                </x-primary-button>
            </x-table-header-actions>
        </x-slot>

        @foreach($budgets as $budget)
            <tr>
                <td>{{ $budget->code }}</td>
                <td><x-status-badge :status="$budget->status" /></td>
                <td><x-table-actions :resource="$budget" /></td>
            </tr>
        @endforeach
    </x-resource-table>
</x-page-container>
```

## Skills Relacionadas

- [budget-ui-components](.kilocode/skills/budget-ui-components/SKILL.md): Componentes de UI para orçamentos
- [reusable-components](.kilocode/skills/reusable-components/SKILL.md): Componentes reutilizáveis comuns
- [layout-navigation-components](.kilocode/skills/layout-navigation-components/SKILL.md): Componentes de layout e navegação

## Padrões de Uso

### Quando Usar Components

- Para elementos reutilizáveis
- Para lógica de apresentação complexa
- Para manter consistência visual

### Quando Usar Pages

- Para páginas completas
- Para composição de componentes
- Para lógica específica de rota
```

## Conclusão

Este documento estabelece a relação entre as skills existentes e os componentes reutilizáveis, fornecendo um guia para atualização e alinhamento com o novo padrão de components. As skills devem ser atualizadas para incluir referências aos componentes relevantes e exemplos de implementação que sigam o padrão atual.
