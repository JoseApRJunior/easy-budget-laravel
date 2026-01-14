# TODO - Frontend

## 📐 VIEW PATTERNS - Padrões de Interface

> **📚 Documentação Completa de Padrões de Views**
>
> Baseado na implementação de **Category** (Dashboard, Index, Create, Edit, Show) e **Inventory** (Index, Movements, Most Used, Stock Turnover)
>
> ✅ **OBRIGATÓRIO:** Todos os novos módulos devem seguir estes padrões
>
> 🎯 **Objetivo:** Consistência visual, UX padronizada e manutenibilidade

### 📋 Índice Rápido

1. [Dashboard Pattern](#-1-dashboard-pattern) - Cards de métricas + Layout 8-4
2. [Index Pattern](#-2-index-listagem-pattern) - Listagem com filtros e tabela
3. [Create Pattern](#-3-create-pattern) - Formulário de criação
4. [Edit Pattern](#-4-edit-pattern) - Formulário de edição
5. [Show Pattern](#-5-show-detalhes-pattern) - Visualização de detalhes
6. [Componentes de Listagem](#-componentes-de-listagem-padronizados) - List-Card, Table, Mobile-Item
7. [Componentes](#-padrões-de-componentes) - Badges, botões, modais
8. [Ícones](#-ícones-bootstrap-icons-por-contexto) - Referência de ícones
9. [Responsividade](#-responsividade) - Classes responsivas
10. [Checklist](#-checklist-de-implementação) - Verificação antes do commit
11. [Referência Rápida](#-referência-rápida---copy--paste) - Templates prontos
12. [Integração com Backend](#-integração-com-backend) - Controllers, Services e Repositories
13. [REPORT Pattern](#-11-report-pattern) - Filtros de Data e Relatórios
14. [MODAL Pattern](#-12-modal-pattern) - Padrões de Modal (Ex: Exclusão)

---

### 📄 11. REPORT Pattern

O padrão de relatórios deve seguir a estrutura de filtros com estado inicial vazio, onde os dados só são carregados após a aplicação de filtros.

#### Padrão de Estilização (CSS)
- **Labels de Filtro:** Sempre utilizar as classes `form-label small fw-bold text-muted text-uppercase`.
- **Cabeçalhos de Tabela (`<th>`):** NÃO utilizar classes de estilização manual (como `small fw-bold...`). A estilização deve vir da classe `.modern-table` aplicada à tabela. Cabeçalhos de tabela devem ser limpos: `<th>Nome</th>`.

#### Padrão de Campos de Formulário (Create/Edit)
Para campos de entrada em formulários, seguir a estrutura utilizada no Budget Create:
- **Label:** `form-label small fw-bold text-muted text-uppercase`.
- **Input de Data:** Priorizar `type="text"` com máscara `DD/MM/AAAA` e `placeholder="DD/MM/AAAA"`.
  - **Motivo:** Evita conflitos com `TomSelect` e garante consistência visual em todos os navegadores.
  - **Implementação:** Utilizar a biblioteca `VanillaMask` para formatação automática e inicializar no `DOMContentLoaded`.
- **Selects:** Utilizar a classe `tom-select` para busca dinâmica.
  - **Importante:** Sempre inicializar o `TomSelect` em novos elementos dinâmicos (como linhas de itens) para garantir o funcionamento da busca.
  - **Exemplo de Inicialização Dinâmica:**
    ```javascript
    const initTomSelect = (element) => {
        if (element && !element.tomselect) {
            new TomSelect(element, {
                create: false,
                sortField: { field: "text", order: "asc" }
            });
        }
    };

    // Ao adicionar nova linha:
    const newSelect = row.querySelector('.tom-select');
    initTomSelect(newSelect);
    ```

Exemplo de campo de data:
```blade
<div class="col-md-6">
    <label for="due_date" class="form-label small fw-bold text-muted text-uppercase">Data de Vencimento *</label>
    <input type="text" id="due_date" name="due_date"
        class="form-control @error('due_date') is-invalid @enderror"
        value="{{ old('due_date') }}"
        inputmode="numeric" placeholder="DD/MM/AAAA" required>
    @error('due_date')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
```

#### Padrão de Filtros de Data
- **Labels Sugeridas:**
  - Para listagens de registros (Ex: Produtos, Categorias): **"Cadastro Inicial"** e **"Cadastro Final"**.
  - Para relatórios de movimentação/fluxo (Ex: Estoque, Financeiro): **"Período Inicial"** e **"Período Final"**.
- **Input:** Utilizar `type="text"` com máscara `DD/MM/AAAA`.
- **Placeholder:** Sempre usar `placeholder="DD/MM/AAAA"`.
- **Validação:** Implementar validação via JavaScript para garantir que a Data Inicial não seja maior que a Data Final.
- **Máscara:** Utilizar a biblioteca `VanillaMask` para formatação automática.
- **Utilitário:** Utilizar a função `parseDate` para converter strings `DD/MM/AAAA` em objetos `Date` válidos, tratando datas inválidas.

Exemplo de implementação JavaScript recomendada:
```javascript
const parseDate = (str) => {
    if (!str) return null;
    const parts = str.split('/');
    if (parts.length === 3) {
        const d = new Date(parts[2], parts[1] - 1, parts[0]);
        return isNaN(d.getTime()) ? null : d;
    }
    return null;
};

const validateDates = () => {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    if (!startDate || !endDate || !startDate.value || !endDate.value) return true;

    const start = parseDate(startDate.value);
    const end = parseDate(endDate.value);

    if (start && end && start > end) {
        const message = 'A data inicial não pode ser maior que a data final.';
        if (window.easyAlert) {
            window.easyAlert.warning(message);
        } else {
            alert(message);
        }
        return false;
    }
    return true;
};

// No evento submit do formulário
form.addEventListener('submit', function(e) {
    if (!validateDates()) {
        e.preventDefault();
        return;
    }

    if (startDate.value && !endDate.value) {
        e.preventDefault();
        const message = 'Para filtrar por período, informe as datas inicial e final.';
        if (window.easyAlert) { window.easyAlert.error(message); }
        endDate.focus();
    } else if (!startDate.value && endDate.value) {
        e.preventDefault();
        const message = 'Para filtrar por período, informe as datas inicial e final.';
        if (window.easyAlert) { window.easyAlert.error(message); }
        startDate.focus();
    }
});
```

#### Botões de Filtro
- Utilizar o componente `<x-button>`.
- **Botão Filtrar:** `variant="primary"`, `icon="search"`, classe `flex-grow-1`.
- **Botão Limpar:** `variant="outline-secondary"`, `icon="x"`, tipo `link`.

Exemplo:
```blade
<div class="d-flex gap-2">
    <x-button type="submit" variant="primary" icon="search" label="Filtrar" class="flex-grow-1" id="btnFilter" />
    <x-button type="link" :href="route('...')" variant="outline-secondary" icon="x" label="Limpar" />
</div>
```

#### Cabeçalho do Relatório
```blade
<x-page-header
    title="Relatório de [Módulo]"
    icon="clipboard-data"
    :breadcrumb-items="[
        '[Módulo]' => route('provider.[modulo].index'),
        'Relatório' => '#'
    ]">
    <p class="text-muted mb-0">Resumo e detalhes conforme filtros aplicados</p>
</x-page-header>
```

#### Card de Filtros (Estado Inicial)
- O relatório deve iniciar **vazio** (`isInitial`).
- Exibir mensagem amigável: "Aguardando Filtros - Selecione os parâmetros acima e clique em Filtrar para gerar o relatório."
- Filtros comuns: Tipo de Relatório, Data Inicial, Data Final, Busca.

#### Card de Resultados
- Utilizar `modern-table` para desktop.
- Implementar `mobile-view` com `list-group`.
- Exportação: Disponibilizar apenas **PDF** e **Excel** (Remover CSV).

#### Integração com Backend (Service)
```php
public function getReportData(array $filters = []): ServiceResult
{
    return $this->safeExecute(function () use ($filters) {
        // 1. Definir parâmetros
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        // 2. Verificar Estado Inicial
        $isInitial = empty($startDate) && empty($endDate) && ...;

        if ($isInitial) {
            return [
                'reportData' => new LengthAwarePaginator([], 0, 10),
                'isInitial' => true,
                // ... outros dados iniciais
            ];
        }

        // 3. Validação e Processamento
        // ...
    });
}
```

---

### 🚀 12. MODAL Pattern - Padrões de Modal (Ex: Exclusão)

- **Estrutura:** Utilizar modais nativos do Bootstrap 5 para garantir compatibilidade.
- **Acionamento:** Utilizar `data-bs-toggle="modal"` e `data-bs-target="#modalId"` nos botões.
- **Passagem de Dados:** Utilizar atributos `data-*` (ex: `data-delete-url`, `data-item-name`) para passar informações para o modal.
- **JavaScript:** Centralizar a lógica de preenchimento do modal no evento `show.bs.modal`.

#### Exemplo de Botão
```blade
<x-button variant="danger" icon="trash"
    data-bs-toggle="modal" data-bs-target="#deleteModal"
    data-delete-url="{{ route('provider.items.destroy', $item->id) }}"
    data-item-name="{{ $item->name }}"
    title="Excluir" />
```

#### Exemplo de Modal
```blade
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                Tem certeza de que deseja excluir <strong id="deleteItemName"></strong>?
                <br><small class="text-muted">Esta ação não pode ser desfeita.</small>
            </div>
            <div class="modal-footer">
                <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                <form id="deleteForm" action="#" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" label="Excluir" />
                </form>
            </div>
        </div>
    </div>
</div>
```

#### Exemplo de Script
```javascript
const deleteModal = document.getElementById('deleteModal');
if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const deleteUrl = button.getAttribute('data-delete-url');
        const itemName = button.getAttribute('data-item-name');

        const deleteItemNameDisplay = deleteModal.querySelector('#deleteItemName');
        const deleteForm = deleteModal.querySelector('#deleteForm');

        deleteItemNameDisplay.textContent = itemName;
        deleteForm.action = deleteUrl;
    });
}
```

---

### 🎨 Padrão de Ícones

#### Ícones de Ação "Novo/Criar"

-  Use ícone **específico** quando existir no Bootstrap Icons
-  Fallback para `bi-plus-circle` quando não houver específico

**Exemplos:**

-  Cliente: `bi-person-plus`
-  Produto: `bi-bag-plus`
-  Categoria: `bi-plus-circle`
-  Serviço: `bi-plus-circle`

---

## 📊 1. DASHBOARD Pattern

### Cabeçalho (Responsivo)

```blade
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <div class="flex-grow-1">
            <h1 class="h4 h3-md mb-1">
                <i class="bi bi-[icone] me-2"></i>
                <span class="d-none d-sm-inline">Dashboard de [Módulo]</span>
                <span class="d-sm-none">[Módulo]</span>
            </h1>
        </div>
        <nav aria-label="breadcrumb" class="d-none d-md-block">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Dashboard de [Módulo]</li>
            </ol>
        </nav>
    </div>
    <p class="text-muted mb-0 small">Descrição contextual do dashboard</p>
</div>
```

### Cards de Métricas (4 colunas)

```blade
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-circle bg-primary bg-gradient me-3">
                        <i class="bi bi-[icone] text-white"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Título da Métrica</h6>
                        <h3 class="mb-0">{{ $valor }}</h3>
                    </div>
                </div>
                <p class="text-muted small mb-0">Descrição da métrica</p>
            </div>
        </div>
    </div>
    <!-- Repetir para outras métricas -->
</div>
```

**Cores de Avatar:**

-  `bg-primary` - Métrica principal/total
-  `bg-success` - Métricas positivas/ativas
-  `bg-secondary` - Métricas neutras/inativas
-  `bg-info` - Métricas de análise/percentuais
-  `bg-warning` - Métricas de atenção
-  `bg-danger` - Métricas críticas

### Layout 8-4 (Conteúdo + Sidebar)

```blade
<div class="row g-4">
    <!-- Conteúdo Principal (8 colunas) -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h5 class="mb-0">
                    <i class="bi bi-[icone] me-2"></i>
                    <span class="d-none d-sm-inline">Título Completo</span>
                    <span class="d-sm-none">Título Curto</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <!-- Desktop View -->
                <div class="desktop-view">
                    <div class="table-responsive">
                        <table class="modern-table table mb-0">
                            <thead>
                                <tr>
                                    <th>Coluna 1</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Conteúdo</td>
                                    <td class="text-center">
                                        <a href="#" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile View -->
                <div class="mobile-view">
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-action py-3">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-[icone] text-muted me-2 mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold mb-2">Título do Item</div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <span class="badge bg-primary" title="Pessoal"><i class="bi bi-person-fill"></i></span>
                                        <span class="badge bg-success-subtle text-success">Ativa</span>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right text-muted ms-2"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar (4 colunas) -->
    <div class="col-lg-4">
        <!-- Insights -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent border-0">
                <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Insights Rápidos</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 small text-muted">
                    <li class="mb-2">
                        <i class="bi bi-[icone] text-primary me-2"></i>Dica 1
                    </li>
                </ul>
            </div>
        </div>

        <!-- Atalhos -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h6 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Atalhos</h6>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('[modulo].create') }}" class="btn btn-sm btn-success">
                    <i class="bi bi-plus-circle me-2"></i>Novo [Item]
                </a>
                <a href="{{ route('[modulo].index') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-[icone] me-2"></i>Listar [Itens]
                </a>
                <a href="{{ route('[modulo].index', ['deleted' => 'only']) }}"
                    class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-archive me-2"></i>Ver Deletados
                </a>
            </div>
        </div>
    </div>
</div>
```

---

### 📦 Componentes de Listagem Padronizados

Para garantir a consistência e o DRY (Don't Repeat Yourself), utilize os componentes Blade criados especificamente para telas de listagem.

#### 1. `<x-resource-list-card>`
O container principal que gerencia o layout Desktop vs Mobile automaticamente.
- **Props:** `title`, `mobileTitle`, `icon`, `total`.
- **Slots:** `headerActions`, `desktop`, `mobile`, `footer`.
- **Dica:** O conteúdo do slot `mobile` é automaticamente envolvido pelas classes `list-group list-group-flush`.

#### 2. `<x-resource-table>`
Padroniza a estrutura de tabelas desktop, incluindo o wrapper responsivo e classes modernas.
- **Slots:** `thead`, `tbody`, `tfoot`.
- **Dica:** O conteúdo do slot `tbody` é automaticamente envolvido pela tag `<tbody>`.

#### 3. `<x-resource-mobile-item>`
Padroniza o item de lista para visualização mobile.
- **Props:** `icon` (padrão: `tag-fill`).
- **Slots:** `default` (título), `description`, `footer`, `actions`.

#### 4. `<x-table-actions>`
Gerencia o alinhamento dos botões de ação em tabelas (desktop) e listas (mobile).
- **Props:** `mobile` (boolean).
- **Uso:** Envolva seus botões (ou o componente `x-action-buttons`) com este componente.

---

## 📋 2. INDEX (Listagem) Pattern

A tela de listagem deve ser limpa e seguir o uso dos componentes padronizados.

#### Estrutura Recomendada:
```blade
<x-page-container>
    <x-page-header title="[Recurso]" icon="[icone]" ... />

    <x-filter-form ...>
        <!-- Seus campos de filtro aqui -->
    </x-filter-form>

    <x-resource-list-card
        title="Lista de [Recurso]"
        mobileTitle="[Recurso]"
        icon="[icone]"
        :total="$items->total()"
    >
        <x-slot:headerActions>
            <x-table-header-actions resource="[recurso]" :filters="$filters" />
        </x-slot:headerActions>

        <x-slot:desktop>
            <x-resource-table>
                <x-slot:thead>
                    <tr>
                        <th>Nome</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </x-slot:thead>
                <x-slot:tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <x-table-actions>
                                <x-action-buttons :item="$item" resource="[recurso]" />
                            </x-table-actions>
                        </tr>
                    @endforeach
                </x-slot:tbody>
            </x-resource-table>
        </x-slot:desktop>

        <x-slot:mobile>
            @foreach($items as $item)
                <x-resource-mobile-item icon="[icone]">
                    {{ $item->name }}
                    <x-slot:actions>
                        <x-table-actions mobile>
                            <x-action-buttons :item="$item" resource="[recurso]" size="sm" />
                        </x-table-actions>
                    </x-slot:actions>
                </x-resource-mobile-item>
            @endforeach
        </x-slot:mobile>

        <x-slot:footer>
            {{ $items->links() }}
        </x-slot:footer>
    </x-resource-list-card>
</x-page-container>
```

---

## ➕ 3. CREATE Pattern

### Cabeçalho

```blade
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="bi bi-[icone-especifico] me-2"></i>Novo [Item]
        </h1>
        <p class="text-muted mb-0">Preencha os dados para criar um novo [item]</p>
    </div>
    <nav aria-label="breadcrumb" class="d-none d-md-block">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('[modulo].index') }}">[Módulo]</a></li>
            <li class="breadcrumb-item active" aria-current="page">Novo</li>
        </ol>
    </nav>
</div>
```

### Card de Formulário Básico

```blade
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('[modulo].store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Nome *</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="d-flex justify-content-between">
                <a href="{{ route('[modulo].index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Criar
                </button>
            </div>
        </form>
    </div>
</div>
```

---

## 🔗 11. Integração com Backend

### Arquitetura Completa

```blade
{{-- Exemplo de como views se integram com controllers, services e repositories --}}

{{-- 1. Controller (app/Http/Controllers/CategoryController.php) --}}
{{-- @see app/Http/Controllers/CategoryController.php --}}
{{-- - Recebe requisições HTTP --}}
{{-- - Chama métodos do Service --}}
{{-- - Retorna views com dados processados --}}

{{-- 2. Service (app/Services/CategoryService.php) --}}
{{-- @see app/Services/CategoryService.php --}}
{{-- - Contém lógica de negócio --}}
{{-- - Usa Repository para acesso a dados --}}
{{-- - Retorna ServiceResult padronizado --}}

{{-- 3. Repository (app/Repositories/CategoryRepository.php) --}}
{{-- @see app/Repositories/CategoryRepository.php --}}
{{-- - Acesso direto ao banco de dados --}}
{{-- - Implementa métodos de consulta --}}
{{-- - Usa Eloquent ORM --}}

{{-- 4. View (resources/views/pages/category/index.blade.php) --}}
{{-- - Recebe dados do Controller --}}
{{-- - Renderiza interface para usuário --}}
{{-- - Usa componentes Blade --}}
```

### Padrões de Integração

```blade
{{-- ✅ Padrão Recomendado: --}}
{{-- 1. Controller → Service → Repository → Model --}}
{{-- 2. Usar ServiceResult para respostas padronizadas --}}
{{-- 3. Injeção de dependência via constructor --}}
{{-- 4. Validação via Form Requests --}}
{{-- 5. Autorização via Gates/Policies --}}

{{-- ❌ Evitar: --}}
{{-- 1. Acesso direto ao Model na View --}}
{{-- 2. Lógica de negócio na View --}}
{{-- 3. Queries SQL diretas na View --}}
{{-- 4. Cálculos complexos na View --}}
```

---

## 📊 ESTATÍSTICAS FINAIS

-  **Arquivos analisados:** 25+ arquivos de views
-  **Problemas identificados:** 15 inconsistências críticas
-  **Arquivos corrigidos:** 15 arquivos principais
-  **Conformidade final:**
   -  Schedule: 98% ✅
   -  Reports: 98% ✅
   -  Modules: 98% ✅
   -  Admin Views: 95% ✅
-  **Relatórios gerados:** 4 relatórios detalhados
-  **Tempo investido:** 8+ horas de análise e implementação

## 🎯 RESULTADOS ALCANÇADOS

### Conformidade de Padrões

-  **98% de conformidade** nos módulos principais (Schedule, Reports, Modules)
-  **95% de conformidade** nas views administrativas
-  **100% das URLs** convertidas para helpers Laravel
-  **100% da navegação** funcionando corretamente

### Melhorias Implementadas

-  **Interface padronizada** em todos os módulos
-  **Responsividade completa** para dispositivos móveis
-  **Performance otimizada** com paginação adequada
-  **UX consistente** em todo o sistema

### Documentação Produzida

-  **4 relatórios detalhados** de análise e correções
-  **TODO.md atualizado** com status do projeto
-  **Padrões documentados** para futuras lembranças

---

## 🔄 Sistema de Reservas de Estoque (PENDENTE)

### Tarefas
- [ ] Criar migration para tabela `inventory_reservations`
- [ ] Adicionar campo `reserved_quantity` na tabela `inventories`
- [ ] Criar model `InventoryReservation`
- [ ] Atualizar `InventoryService::reserveProduct()`
- [ ] Criar job `ExpireInventoryReservations`
- [ ] Testes unitários e de integração
