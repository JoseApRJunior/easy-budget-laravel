# Reusable Components (Componentes Reutilizáveis)

## 🎯 Descrição

Esta skill identifica e documenta os componentes que podem ser reaproveitados entre diferentes módulos do sistema Easy Budget Laravel. O objetivo é promover a consistência, reduzir duplicação de código e acelerar o desenvolvimento de novas funcionalidades.

## 📦 Componentes Base Comuns

### **1. Botões (Button)**

**Descrição:** Componente de botão flexível com múltiplas variantes e estilos.

**Módulos onde pode ser reutilizado:**
- Todas as páginas de listagem (Categories, Products, Customers, Budgets, Services, Invoices)
- Formulários de criação e edição
- Modais de confirmação
- Dashboards e cards de ação

**Parâmetros e Props:**
```php
@props([
    'variant' => 'primary',     // primary, secondary, success, danger, warning, info
    'outline' => false,         // Estilo outline
    'icon' => null,             // Ícone Bootstrap Icons
    'size' => null,             // sm, lg, null
    'type' => 'button',         // button, link, submit
    'href' => null,             // URL para links
    'label' => null,            // Texto do botão
])
```

**Exemplos de Uso:**
```blade
{{-- Botão primário com ícone --}}
<x-button variant="primary" icon="plus" label="Novo" />

{{-- Botão de link --}}
<x-button type="link" href="{{ route('categories.create') }}" variant="success" icon="plus">
    Criar Categoria
</x-button>

{{-- Botão perigoso --}}
<x-button variant="danger" outline icon="trash" onclick="confirmDelete()">
    Excluir
</x-button>
```

**Estilos e Classes CSS:**
- Classes Bootstrap: `btn`, `btn-primary`, `btn-outline-*`, `btn-sm`, `btn-lg`
- Ícones: `bi bi-*` (Bootstrap Icons)
- Espaçamento: `me-2` para ícones com texto

**JavaScript e Interatividade:**
- Suporte a eventos onclick
- Compatível com modais de confirmação
- Integração com formulários

---

### **2. Cabeçalho de Página (Page Header)**

**Descrição:** Componente de cabeçalho padronizado com breadcrumb, título e ações.

**Módulos onde pode ser reutilizado:**
- Todas as páginas principais do sistema
- Dashboards específicos
- Páginas de detalhes e formulários

**Parâmetros e Props:**
```php
@props([
    'title',                    // Título da página
    'icon' => null,             // Ícone do título
    'breadcrumbItems' => [],    // Array de breadcrumbs
])
```

**Exemplos de Uso:**
```blade
<x-page-header
    title="Gerenciar Produtos"
    icon="box"
    :breadcrumb-items="[
        'Dashboard' => route('provider.dashboard'),
        'Produtos' => route('provider.products.index'),
        'Listagem' => '#'
    ]">
    <p class="text-muted mb-0">Controle seu catálogo de produtos</p>
</x-page-header>
```

**Estilos e Classes CSS:**
- Layout flexível com responsividade
- Breadcrumb integrado
- Espaçamento consistente

---

### **3. Container de Página (Page Container)**

**Descrição:** Container responsivo para conteúdo de páginas.

**Módulos onde pode ser reutilizado:**
- Todas as páginas do sistema
- Dashboards
- Formulários e listagens

**Parâmetros e Props:**
```php
@props([
    'fluid' => true,            // container-fluid ou container
    'padding' => 'py-2',        // Classes de padding
])
```

**Exemplos de Uso:**
```blade
<x-page-container fluid padding="py-4">
    <!-- Conteúdo da página -->
</x-page-container>
```

---

## 🏷️ Componentes de Status

### **1. Badges de Status (Status Badge)**

**Descrição:** Componente para exibir status de registros com cores e ícones.

**Módulos onde pode ser reutilizado:**
- Listagens de Budgets (status: active, inactive, deleted)
- Listagens de Products (active, inactive)
- Listagens de Services (status específicos)
- Listagens de Invoices (status de pagamento)
- Listagens de Customers (status: active, inactive)

**Parâmetros e Props:**
```php
@props([
    'item',                      // Modelo/objeto
    'statusField' => 'status',   // Campo de status
    'activeLabel' => 'Ativo',    // Label para ativo
    'inactiveLabel' => 'Inativo', // Label para inativo
    'deletedLabel' => 'Deletado', // Label para deletado
])
```

**Exemplos de Uso:**
```blade
{{-- Status de Produto --}}
<x-status-badge :item="$product" status-field="active" />

{{-- Status de Orçamento --}}
<x-status-badge :item="$budget" status-field="status" />

{{-- Status de Fatura --}}
<x-status-badge :item="$invoice" status-field="status" />
```

**Estilos e Classes CSS:**
- Cores baseadas no status (verde para ativo, cinza para inativo, vermelho para deletado)
- Classes: `modern-badge`, `badge-active`, `badge-inactive`, `badge-deleted`
- Ícones opcionais

---

### **2. Descrição de Status (Status Description)**

**Descrição:** Componente para exibir descrição detalhada de status.

**Módulos onde pode ser reutilizado:**
- Páginas de detalhes de Budgets
- Páginas de detalhes de Services
- Páginas de detalhes de Invoices
- Páginas de detalhes de Customers

**Parâmetros e Props:**
```php
@props([
    'status',                    // Objeto de status
    'showIcon' => true,          // Exibir ícone
])
```

**Exemplos de Uso:**
```blade
<x-status-description :status="$budget->status" />
<x-status-description :status="$service->status" show-icon="false" />
```

---

## 🎛️ Componentes de Ação

### **1. Grupos de Ações (Action Buttons)**

**Descrição:** Componente para agrupar botões de ação com lógica de exclusão/restauração.

**Módulos onde pode ser reutilizado:**
- Tabelas de listagem (Categories, Products, Customers, Budgets, Services, Invoices)
- Cards de recursos
- Modais de detalhes

**Parâmetros e Props:**
```php
@props([
    'item',                      // Modelo/objeto
    'resource',                  // Nome do recurso (categories, products, etc.)
    'identifier' => 'id',        // Campo identificador
    'nameField' => 'name',       // Campo para nome
    'canDelete' => true,         // Pode excluir
    'restoreBlocked' => false,   // Restauração bloqueada
    'restoreBlockedMessage' => 'Não é possível restaurar este item no momento.',
    'size' => null,              // Tamanho dos botões
])
```

**Exemplos de Uso:**
```blade
{{-- Ações para Categoria --}}
<x-action-buttons
    :item="$category"
    resource="categories"
    identifier="slug"
    nameField="name"
/>

{{-- Ações para Produto --}}
<x-action-buttons
    :item="$product"
    resource="products"
    identifier="sku"
    nameField="name"
/>
```

**Estilos e Classes CSS:**
- Classes: `action-btn-group`
- Botões com variantes específicas
- Ícones: `bi-eye`, `bi-pencil`, `bi-trash`, `bi-arrow-clockwise`

---

### **2. Botões de Confirmação (Confirm Modal)**

**Descrição:** Componente para modais de confirmação de ações perigosas.

**Módulos onde pode ser reutilizado:**
- Exclusão de registros (Categories, Products, Customers, Budgets, Services, Invoices)
- Ações de restauração
- Ações de mudança de status

**Parâmetros e Props:**
```php
@props([
    'id',                        // ID do modal
    'title',                     // Título do modal
    'message',                   // Mensagem de confirmação
    'confirmText' => 'Confirmar', // Texto do botão de confirmação
    'cancelText' => 'Cancelar',   // Texto do botão de cancelar
    'confirmClass' => 'btn-danger', // Classe do botão de confirmação
])
```

**Exemplos de Uso:**
```blade
<x-confirm-modal
    id="deleteCategoryModal"
    title="Excluir Categoria"
    message="Tem certeza que deseja excluir esta categoria? Esta ação não pode ser desfeita."
    confirmText="Excluir"
    cancelText="Cancelar"
    confirmClass="btn-danger"
/>
```

---

## 📝 Componentes de Formulário

### **1. Campos de Entrada (Text Input)**

**Descrição:** Campo de entrada de texto padrão.

**Módulos onde pode ser reutilizado:**
- Todos os formulários de criação e edição
- Campos de busca e filtros
- Campos de configuração

**Parâmetros e Props:**
```php
@props(['disabled' => false])
```

**Exemplos de Uso:**
```blade
<x-text-input type="text" name="name" disabled="{{ $readonly }}" />
<x-text-input type="email" name="email" />
<x-text-input type="password" name="password" />
```

**Estilos e Classes CSS:**
- Classes Bootstrap: `border-gray-300`, `dark:border-gray-700`
- Foco: `focus:border-indigo-500`, `focus:ring-indigo-500`
- Arredondamento: `rounded-md`
- Sombra: `shadow-sm`

---

### **2. Rótulos de Entrada (Input Label)**

**Descrição:** Rótulo para campos de formulário.

**Módulos onde pode ser reutilizado:**
- Todos os formulários do sistema
- Campos de configuração
- Campos de filtro

**Parâmetros e Props:**
```php
@props(['value'])
```

**Exemplos de Uso:**
```blade
<x-input-label for="name" :value="__('Nome')" />
<x-input-label for="email" :value="__('E-mail')" />
```

---

### **3. Erros de Entrada (Input Error)**

**Descrição:** Exibição de erros de validação.

**Módulos onde pode ser reutilizado:**
- Todos os formulários com validação
- Campos de login e registro
- Campos de configuração

**Parâmetros e Props:**
```php
@props(['messages'])
```

**Exemplos de Uso:**
```blade
<x-input-error :messages="$errors->get('name')" />
<x-input-error :messages="$errors->get('email')" />
```

---

### **4. Campos de Filtro (Filter Field)**

**Descrição:** Campo de filtro com lógica de limpeza.

**Módulos onde pode ser reutilizado:**
- Páginas de listagem com filtros
- Dashboards com filtros
- Relatórios com parâmetros

**Parâmetros e Props:**
```php
@props([
    'name',                      // Nome do campo
    'label',                     // Rótulo do campo
    'value' => '',               // Valor atual
    'type' => 'text',            // Tipo do campo
    'options' => [],             // Opções para selects
    'placeholder' => '',         // Placeholder
])
```

**Exemplos de Uso:**
```blade
<x-filter-field
    name="search"
    label="Buscar"
    value="{{ $filters['search'] ?? '' }}"
    placeholder="Digite para buscar..."
/>

<x-filter-field
    name="status"
    label="Status"
    type="select"
    :options="['active' => 'Ativo', 'inactive' => 'Inativo']"
    value="{{ $filters['status'] ?? '' }}"
/>
```

---

## 📊 Componentes de Tabela

### **1. Tabelas de Recursos (Resource Table)**

**Descrição:** Tabela padrão para listagem de recursos com ações.

**Módulos onde pode ser reutilizado:**
- Listagens de Categories
- Listagens de Products
- Listagens de Customers
- Listagens de Budgets
- Listagens de Services
- Listagens de Invoices

**Parâmetros e Props:**
```php
@props([
    'items',                     // Coleção de itens
    'columns',                   // Definição de colunas
    'actions' => true,           // Exibir coluna de ações
    'mobileActions' => false,    // Ações em mobile
    'emptyMessage' => 'Nenhum registro encontrado', // Mensagem vazia
])
```

**Exemplos de Uso:**
```blade
<x-resource-table
    :items="$categories"
    :columns="[
        ['field' => 'name', 'label' => 'Nome', 'sortable' => true],
        ['field' => 'created_at', 'label' => 'Criado em', 'type' => 'datetime'],
    ]"
    actions="true"
    emptyMessage="Nenhuma categoria encontrada"
/>
```

**Estilos e Classes CSS:**
- Classes Bootstrap: `table`, `table-hover`, `table-striped`
- Responsividade: `table-responsive`
- Ações: `action-btn-group`

---

### **2. Cabeçalho de Ações (Table Header Actions)**

**Descrição:** Cabeçalho de tabela com botões de ação.

**Módulos onde pode ser reutilizado:**
- Tabelas de listagem
- Dashboards com tabelas
- Relatórios em formato de tabela

**Parâmetros e Props:**
```php
@props([
    'title' => '',               // Título da tabela
    'actions' => [],             // Botões de ação
    'filters' => false,          // Exibir filtros
])
```

**Exemplos de Uso:**
```blade
<x-table-header-actions
    title="Lista de Produtos"
    :actions="[
        ['url' => route('products.create'), 'label' => 'Novo Produto', 'icon' => 'plus', 'variant' => 'primary'],
        ['url' => route('products.export'), 'label' => 'Exportar', 'icon' => 'download', 'variant' => 'secondary'],
    ]"
    filters="true"
/>
```

---

### **3. Células de Data/Hora (Table Cell Datetime)**

**Descrição:** Formatação consistente de datas e horas em tabelas.

**Módulos onde pode ser reutilizado:**
- Todas as tabelas com campos de data
- Listagens de Budgets
- Listagens de Services
- Listagens de Invoices
- Listagens de Movimentos de Estoque

**Parâmetros e Props:**
```php
@props([
    'datetime',                  // Data/hora a ser formatada
    'showTime' => true,          // Exibir hora
    'stack' => true,             // Empilhar data e hora
])
```

**Exemplos de Uso:**
```blade
<x-table-cell-datetime :datetime="$budget->created_at" />
<x-table-cell-datetime :datetime="$service->due_date" show-time="false" />
<x-table-cell-datetime :datetime="$invoice->updated_at" stack="false" />
```

**Estilos e Classes CSS:**
- Classes: `small`, `text-muted`, `fw-bold`, `text-dark`
- Formato: `d/m/Y H:i` (Brasil)

---

## 🪟 Componentes de Modal

### **1. Modais Genéricos (Modal)**

**Descrição:** Componente de modal Bootstrap configurável.

**Módulos onde pode ser reutilizado:**
- Confirmações de exclusão
- Visualização de detalhes
- Formulários em modal
- Mensagens de informação

**Parâmetros e Props:**
```php
@props([
    'id',                        // ID do modal
    'title',                     // Título do modal
    'size' => '',                // Tamanho (modal-sm, modal-lg, modal-xl)
    'centered' => true,          // Centralizado
    'scrollable' => false,       // Rolagem
])
```

**Exemplos de Uso:**
```blade
<x-modal id="viewDetailsModal" title="Detalhes" size="modal-lg">
    <!-- Conteúdo do modal -->
</x-modal>

<x-modal id="confirmDeleteModal" title="Confirmação" size="modal-sm" centered="true">
    <p>Tem certeza que deseja excluir?</p>
    <x-slot name="footer">
        <button class="btn btn-danger">Excluir</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    </x-slot>
</x-modal>
```

**Estilos e Classes CSS:**
- Classes Bootstrap: `modal`, `modal-dialog`, `modal-content`
- Tamanhos: `modal-sm`, `modal-lg`, `modal-xl`
- Posicionamento: `modal-dialog-centered`, `modal-dialog-scrollable`

---

### **2. Modais de Confirmação (Confirm Modal)**

**Descrição:** Modal especializado para confirmações de ações.

**Módulos onde pode ser reutilizado:**
- Exclusão de registros
- Mudança de status
- Ações irreversíveis

**Parâmetros e Props:**
```php
@props([
    'id',                        // ID do modal
    'title',                     // Título
    'message',                   // Mensagem de confirmação
    'confirmText' => 'Confirmar', // Texto do botão de confirmação
    'cancelText' => 'Cancelar',   // Texto do botão de cancelar
    'confirmClass' => 'btn-danger', // Classe do botão de confirmação
])
```

**Exemplos de Uso:**
```blade
<x-confirm-modal
    id="deleteModal"
    title="Excluir Registro"
    message="Esta ação não pode ser desfeita. Deseja continuar?"
    confirmText="Excluir"
    cancelText="Cancelar"
    confirmClass="btn-danger"
/>
```

---

## 📤 Componentes de Upload

### **1. Upload de Arquivos (File Upload)**

**Descrição:** Componente para upload de arquivos com validação.

**Módulos onde pode ser reutilizado:**
- Upload de logo de empresa
- Upload de imagens de produtos
- Upload de documentos de clientes
- Upload de anexos de orçamentos

**Parâmetros e Props:**
```php
@props([
    'name',                      // Nome do campo
    'label' => 'Upload de Arquivo', // Rótulo
    'accept' => '*',             // Tipos aceitos
    'maxSize' => '2048',         // Tamanho máximo em KB
    'preview' => true,           // Exibir preview
    'currentFile' => null,       // Arquivo atual
])
```

**Exemplos de Uso:**
```blade
<x-file-upload
    name="logo"
    label="Logo da Empresa"
    accept="image/*"
    max-size="1024"
    preview="true"
    :current-file="$provider->logo"
/>

<x-file-upload
    name="document"
    label="Documento"
    accept=".pdf,.doc,.docx"
    max-size="5120"
/>
```

**Estilos e Classes CSS:**
- Classes Bootstrap: `form-control`
- Preview: `img-thumbnail`
- Mensagens de erro: `text-danger`

**JavaScript e Interatividade:**
- Preview de imagens
- Validação de tamanho e tipo
- Remoção de arquivos

---

### **2. Upload de Imagens (Image Upload)**

**Descrição:** Componente especializado para upload de imagens.

**Módulos onde pode ser reutilizado:**
- Upload de imagens de produtos
- Upload de logo de empresa
- Upload de fotos de perfil
- Upload de imagens de clientes

**Parâmetros e Props:**
```php
@props([
    'name',                      // Nome do campo
    'label' => 'Upload de Imagem', // Rótulo
    'maxSize' => '2048',         // Tamanho máximo em KB
    'aspectRatio' => '1',        // Proporção (1:1, 16:9, etc.)
    'currentImage' => null,      // Imagem atual
    'placeholder' => 'assets/img/placeholder-image.png', // Imagem placeholder
])
```

**Exemplos de Uso:**
```blade
<x-image-upload
    name="image"
    label="Imagem do Produto"
    max-size="1024"
    aspect-ratio="1"
    :current-image="$product->image"
/>

<x-image-upload
    name="logo"
    label="Logo da Empresa"
    max-size="512"
    aspect-ratio="4"
    :current-image="$provider->logo"
/>
```

---

## 📢 Componentes de Notificação

### **1. Alertas (Alert)**

**Descrição:** Componente de alerta Bootstrap com tipos variados.

**Módulos onde pode ser reutilizado:**
- Mensagens de sucesso após operações
- Mensagens de erro de validação
- Mensagens de informação
- Mensagens de aviso

**Parâmetros e Props:**
```php
@props([
    'type' => 'info',            // success, danger, warning, info
    'message',                   // Mensagem do alerta
    'dismissible' => true,       // Pode ser fechado
    'icon' => null,              // Ícone personalizado
])
```

**Exemplos de Uso:**
```blade
<x-alert type="success" message="Operação realizada com sucesso!" />
<x-alert type="danger" message="Erro ao processar a solicitação." dismissible="true" />
<x-alert type="warning" message="Atenção: Esta ação não pode ser desfeita." icon="exclamation-triangle" />
```

**Estilos e Classes CSS:**
- Classes Bootstrap: `alert`, `alert-success`, `alert-danger`, `alert-warning`, `alert-info`
- Dismissible: `alert-dismissible`
- Ícones: `bi bi-*`

---

### **2. Mensagens de Sessão (Session Messages)**

**Descrição:** Exibição de mensagens flash da sessão.

**Módulos onde pode ser reutilizado:**
- Todas as páginas após operações
- Páginas de formulário
- Páginas de listagem

**Parâmetros e Props:**
```php
@props([
    'type' => null,              // Tipo específico de mensagem
    'class' => 'mb-3',           // Classes CSS
])
```

**Exemplos de Uso:**
```blade
@include('partials.components.alerts')

{{-- Mensagens específicas --}}
@if(session('success'))
    <x-alert type="success" :message="session('success')" />
@endif

@if(session('error'))
    <x-alert type="danger" :message="session('error')" />
@endif
```

---

### **3. Estado de Sessão de Autenticação (Auth Session Status)**

**Descrição:** Exibição de status de autenticação.

**Módulos onde pode ser reutilizado:**
- Páginas de login
- Páginas de registro
- Páginas de redefinição de senha

**Parâmetros e Props:**
```php
@props(['status'])
```

**Exemplos de Uso:**
```blade
<x-auth-session-status class="mb-4" :status="session('status')" />
```

---

## 📈 Componentes de Dashboard

### **1. Cards de Métricas (Stat Card)**

**Descrição:** Card para exibição de métricas e estatísticas.

**Módulos onde pode ser reutilizado:**
- Dashboard principal
- Dashboards específicos por módulo
- Cards de resumo
- Cards de KPIs

**Parâmetros e Props:**
```php
@props([
    'title',                     // Título do card
    'value',                     // Valor principal
    'description' => null,       // Descrição secundária
    'icon' => null,              // Ícone
    'variant' => 'primary',      // primary, success, info, warning, danger, secondary
    'gradient' => true,          // Gradiente de cor
    'isCustom' => false,         // Layout customizado
])
```

**Exemplos de Uso:**
```blade
<x-stat-card
    title="Total de Clientes"
    value="{{ $totalCustomers }}"
    description="Clientes ativos"
    icon="people"
    variant="primary"
/>

<x-stat-card
    title="Receita do Mês"
    value="R$ {{ number_format($revenue, 2, ',', '.') }}"
    description="Faturamento"
    icon="currency-dollar"
    variant="success"
/>
```

**Estilos e Classes CSS:**
- Classes: `card`, `border-0`, `shadow-sm`
- Variantes: `bg-primary`, `bg-success`, `bg-info`, etc.
- Ícones: `bi bi-*`
- Espaçamento: `p-3`, `me-2`

---

### **2. Cards de Recursos (Resource List Card)**

**Descrição:** Card para exibição de recursos individuais.

**Módulos onde pode ser reutilizado:**
- Listagens em formato de cards
- Dashboards de recursos
- Galerias de produtos
- Listas de categorias

**Parâmetros e Props:**
```php
@props([
    'title',                     // Título do card
    'subtitle' => null,          // Subtítulo
    'icon' => null,              // Ícone
    'iconClass' => null,         // Classes do ícone
    'titleClass' => null,        // Classes do título
    'actions' => null,           // Ações do card
    'status' => null,            // Status do recurso
])
```

**Exemplos de Uso:**
```blade
<x-resource-list-card
    title="Categoria Principal"
    subtitle="Produtos eletrônicos"
    icon="folder"
    :actions="[
        ['url' => route('categories.edit', $category->slug), 'icon' => 'pencil', 'variant' => 'primary'],
        ['url' => '#', 'icon' => 'trash', 'variant' => 'danger', 'onclick' => 'confirmDelete()'],
    ]"
    :status="$category->status"
/>
```

---

### **3. Gráficos (Charts)**

**Descrição:** Componente para exibição de gráficos Chart.js.

**Módulos onde pode ser reutilizado:**
- Dashboard financeiro
- Dashboard de vendas
- Dashboard de estoque
- Relatórios analíticos

**Parâmetros e Props:**
```php
@props([
    'id',                        // ID do canvas
    'type',                      // Tipo de gráfico (line, bar, pie, doughnut)
    'data',                      // Dados do gráfico
    'options' => [],             // Opções do gráfico
    'height' => '400',           // Altura
    'width' => '100%',           // Largura
])
```

**Exemplos de Uso:**
```blade
<x-chart
    id="revenueChart"
    type="line"
    :data="$chartData"
    :options="$chartOptions"
    height="300"
/>

<x-chart
    id="categoryChart"
    type="doughnut"
    :data="$categoryData"
    width="400"
/>
```

**JavaScript e Interatividade:**
- Chart.js integrado
- Responsividade
- Tooltips
- Animações

---

## 🔧 Componentes de Upload Avançados

### **1. Upload de Múltiplos Arquivos (Multi File Upload)**

**Descrição:** Upload de múltiplos arquivos simultaneamente.

**Módulos onde pode ser reutilizado:**
- Upload de imagens de produtos
- Upload de documentos de clientes
- Upload de anexos de orçamentos
- Upload de arquivos de serviços

**Parâmetros e Props:**
```php
@props([
    'name',                      // Nome do campo
    'label' => 'Upload de Arquivos', // Rótulo
    'accept' => '*',             // Tipos aceitos
    'maxFiles' => 5,             // Máximo de arquivos
    'maxSize' => '2048',         // Tamanho máximo por arquivo
    'preview' => true,           // Exibir preview
    'existingFiles' => [],       // Arquivos existentes
])
```

**Exemplos de Uso:**
```blade
<x-multi-file-upload
    name="images"
    label="Imagens do Produto"
    accept="image/*"
    max-files="10"
    max-size="1024"
    preview="true"
    :existing-files="$product->images"
/>
```

---

### **2. Upload com Drag and Drop (Drag Drop Upload)**

**Descrição:** Upload com arrastar e soltar.

**Módulos onde pode ser reutilizado:**
- Upload de imagens
- Upload de documentos
- Upload de arquivos grandes

**Parâmetros e Props:**
```php
@props([
    'name',                      // Nome do campo
    'label' => 'Arraste e solte arquivos aqui', // Rótulo
    'accept' => '*',             // Tipos aceitos
    'maxSize' => '2048',         // Tamanho máximo
    'multiple' => true,          // Múltiplos arquivos
])
```

**Exemplos de Uso:**
```blade
<x-drag-drop-upload
    name="files"
    label="Arraste e solte arquivos aqui"
    accept="image/*,application/pdf"
    max-size="5120"
    multiple="true"
/>
```

**JavaScript e Interatividade:**
- Drag and drop
- Preview de arquivos
- Validação em tempo real
- Progresso de upload

---

## 📱 Componentes Mobile

### **1. Itens Mobile (Resource Mobile Item)**

**Descrição:** Layout otimizado para visualização em dispositivos móveis.

**Módulos onde pode ser reutilizado:**
- Listagens em mobile
- Dashboards responsivos
- Cards de recursos

**Parâmetros e Props:**
```php
@props([
    'title',                     // Título
    'subtitle' => null,          // Subtítulo
    'description' => null,       // Descrição
    'actions' => [],             // Ações
    'status' => null,            // Status
    'image' => null,             // Imagem
])
```

**Exemplos de Uso:**
```blade
<x-resource-mobile-item
    title="Produto Exemplo"
    subtitle="Categoria: Eletrônicos"
    description="Produto de alta qualidade"
    :actions="[
        ['url' => route('products.edit', $product->sku), 'icon' => 'pencil', 'label' => 'Editar'],
        ['url' => route('products.show', $product->sku), 'icon' => 'eye', 'label' => 'Ver'],
    ]"
    :status="$product->status"
    image="{{ $product->image }}"
/>
```

---

### **2. Ações Mobile (Table Actions Mobile)**

**Descrição:** Grupo de ações otimizado para mobile.

**Módulos onde pode ser reutilizado:**
- Tabelas em mobile
- Cards de recursos
- Listagens responsivas

**Parâmetros e Props:**
```php
@props([
    'mobile' => true,            // Modo mobile
])
```

**Exemplos de Uso:**
```blade
<x-table-actions mobile="true">
    <x-button type="link" href="{{ route('products.edit', $product->sku) }}" variant="primary" icon="pencil" size="sm" />
    <x-button type="link" href="{{ route('products.show', $product->sku) }}" variant="info" icon="eye" size="sm" />
    <x-button type="button" variant="danger" icon="trash" size="sm" onclick="confirmDelete({{ $product->id }})" />
</x-table-actions>
```

---

## 🎨 Componentes de Estilo

### **1. Separadores (Separator)**

**Descrição:** Separador visual entre seções.

**Módulos onde pode ser reutilizado:**
- Formulários longos
- Cards com múltiplas seções
- Dashboards com múltiplos widgets

**Parâmetros e Props:**
```php
@props([
    'type' => 'horizontal',      // horizontal, vertical
    'size' => 'md',              // sm, md, lg
    'color' => 'light',          // light, dark, primary
])
```

**Exemplos de Uso:**
```blade
<hr class="my-4" />
<x-separator type="horizontal" size="md" color="light" />
```

---

### **2. Espaçadores (Spacer)**

**Descrição:** Espaçador para layout.

**Módulos onde pode ser reutilizado:**
- Layouts de formulário
- Cards com múltiplos elementos
- Dashboards

**Parâmetros e Props:**
```php
@props([
    'size' => 'md',              // sm, md, lg, xl
    'direction' => 'vertical',   // vertical, horizontal
])
```

**Exemplos de Uso:**
```blade
<div class="mb-3"></div>
<x-spacer size="md" direction="vertical" />
```

---

## 📋 Diretrizes de Uso

### **✅ Quando usar componentes reutilizáveis:**

1. **Consistência visual** - Quando precisar do mesmo padrão em múltiplos lugares
2. **Redução de código** - Quando houver duplicação de lógica de UI
3. **Manutenção** - Quando mudanças precisarem ser aplicadas em múltiplos lugares
4. **Produtividade** - Quando acelerar o desenvolvimento de novas funcionalidades

### **❌ Quando NÃO usar componentes reutilizáveis:**

1. **Casos muito específicos** - Quando o componente só será usado uma vez
2. **Lógica muito complexa** - Quando o componente ficaria muito complicado
3. **Requisitos muito diferentes** - Quando as variações seriam maiores que o padrão

### **🔧 Boas práticas:**

1. **Props claras** - Sempre documentar os parâmetros esperados
2. **Valores padrão** - Definir valores padrão quando possível
3. **Flexibilidade** - Permitir customização através de classes CSS
4. **Testabilidade** - Facilitar testes unitários e de integração
5. **Documentação** - Manter exemplos de uso atualizados

### **📊 Métricas de sucesso:**

- **Redução de código duplicado** em 60%
- **Aumento de consistência visual** em 90%
- **Redução de tempo de desenvolvimento** em 40%
- **Aumento de manutenibilidade** em 80%

---

## 🔄 Evolução Futura

### **Próximos componentes planejados:**

1. **Componentes de busca avançada**
2. **Componentes de filtros dinâmicos**
3. **Componentes de exportação**
4. **Componentes de importação**
5. **Componentes de integração com APIs**
6. **Componentes de notificações em tempo real**
7. **Componentes de ajuda contextual**
8. **Componentes de tour guiado**

### **Melhorias planejadas:**

1. **Sistema de temas** - Componentes com suporte a múltiplos temas
2. **Acessibilidade** - Melhor suporte a leitores de tela
3. **Performance** - Otimização de renderização
4. **Testes** - Cobertura de testes completa
5. **Documentação** - Documentação interativa

---

**Última atualização:** 11/01/2026 - Documentação inicial dos componentes reutilizáveis
