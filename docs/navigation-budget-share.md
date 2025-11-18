# Estrutura de Navegação - Budget Share (Compartilhamento de Orçamentos)

## Visão Geral

Este documento descreve a estrutura de navegação implementada para o módulo de compartilhamento de orçamentos do sistema Easy Budget.

## Hierarquia de Navegação

A estrutura segue a hierarquia lógica:
```
Dashboard (Provider) > Orçamentos > Compartilhamentos > [Página Específica]
```

## Rotas e Páginas

### 1. Dashboard de Compartilhamentos
- **URL**: `/provider/budgets/shares/dashboard`
- **Rota**: `provider.budgets.shares.dashboard`
- **Breadcrumb**: Dashboard > Orçamentos > Compartilhamentos > Dashboard
- **Descrição**: Página inicial com métricas e estatísticas dos compartilhamentos

### 2. Lista de Compartilhamentos
- **URL**: `/provider/budgets/shares`
- **Rota**: `provider.budgets.shares.index`
- **Breadcrumb**: Dashboard > Orçamentos > Compartilhamentos
- **Descrição**: Lista paginada de todos os compartilhamentos do tenant

### 3. Criar Novo Compartilhamento
- **URL**: `/provider/budgets/shares/create`
- **Rota**: `provider.budgets.shares.create`
- **Breadcrumb**: Dashboard > Orçamentos > Compartilhamentos > Criar
- **Descrição**: Formulário para criar novo compartilhamento

### 4. Detalhes do Compartilhamento
- **URL**: `/provider/budgets/shares/{id}`
- **Rota**: `provider.budgets.shares.show`
- **Breadcrumb**: Dashboard > Orçamentos > Compartilhamentos > Detalhes
- **Descrição**: Visualização detalhada de um compartilhamento específico

### 5. Editar Compartilhamento
- **URL**: `/provider/budgets/shares/{id}/edit`
- **Rota**: `provider.budgets.shares.edit`
- **Breadcrumb**: Dashboard > Orçamentos > Compartilhamentos > Editar
- **Descrição**: Formulário para editar compartilhamento existente

## Estrutura de Breadcrumb

Todos os breadcrumbs seguem o padrão Bootstrap 5 com as seguintes características:

```html
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('provider.budgets.index') }}">Orçamentos</a></li>
        <li class="breadcrumb-item"><a href="{{ route('provider.budgets.shares.index') }}">Compartilhamentos</a></li>
        <li class="breadcrumb-item active" aria-current="page">[Página Atual]</li>
    </ol>
</nav>
```

### Página Atual por Tipo

- **Dashboard**: "Dashboard" (active)
- **Index**: "Compartilhamentos" (active)
- **Create**: "Criar" (active)
- **Show**: "Detalhes" (active)
- **Edit**: "Editar" (active)

## Navegação entre Páginas

### Fluxo Principal
1. **Dashboard Provider** → **Orçamentos** → **Compartilhamentos**
2. **Compartilhamentos** → **Dashboard de Compartilhamentos** (via botão ou link)
3. **Compartilhamentos** → **Criar Novo** (via botão "Novo Compartilhamento")
4. **Lista** → **Detalhes** (via clique no item ou botão "Ver")
5. **Detalhes** → **Editar** (via botão "Editar")
6. **Qualquer página** → **Voltar** (via breadcrumb ou botão voltar)

### Botões de Ação

#### Dashboard de Compartilhamentos
- "Novo Compartilhamento" → `/provider/budgets/shares/create`
- "Gerenciar Compartilhamentos" → `/provider/budgets/shares`
- "Ver Orçamentos" → `/provider/budgets`
- "Relatórios" → `/provider/reports/budgets`

#### Lista de Compartilhamentos
- "Voltar aos Orçamentos" → `/provider/budgets`
- "Novo Compartilhamento" → `/provider/budgets/shares/create`
- Links de edição e visualização para cada item

## Consistência Visual

### Padrões Aplicados

1. **Estrutura de Container**:
   ```html
   <div class="container-fluid py-1">
       <!-- Page Header -->
       <div class="d-flex justify-content-between align-items-center mb-4">
           <h1 class="h3 mb-0">
               <i class="bi bi-[icon] me-2"></i>
               [Título da Página]
           </h1>
           <!-- Breadcrumb aqui -->
       </div>
       <!-- Conteúdo -->
   </div>
   ```

2. **Ícones Bootstrap Icons**:
   - Dashboard: `bi bi-share-fill`
   - Lista: `bi bi-share`
   - Criar: `bi bi-share`
   - Detalhes: `bi bi-share`
   - Editar: `bi bi-pencil`

3. **Cores e Estilos**:
   - Títulos: Texto padrão (herdado do tema)
   - Links: Bootstrap default
   - Active breadcrumb: Bootstrap default
   - Cards: `border-0 shadow-sm` para consistência

## Rotas Disponíveis

Comando `php artisan route:list --name="budgets.shares"` mostra:

```
GET|HEAD  provider/budgets/shares                    provider.budgets.shares.index
POST      provider/budgets/shares                    provider.budgets.shares.store  
GET|HEAD  provider/budgets/shares/create           provider.budgets.shares.create
GET|HEAD  provider/budgets/shares/dashboard          provider.budgets.shares.dashboard
GET|HEAD  provider/budgets/shares/{share}            provider.budgets.shares.show
PUT       provider/budgets/shares/{share}            provider.budgets.shares.update
DELETE    provider/budgets/shares/{share}            provider.budgets.shares.destroy
GET|HEAD  provider/budgets/shares/{share}/edit       provider.budgets.shares.edit
POST      provider/budgets/shares/{share}/regenerate provider.budgets.shares.regenerate
POST      provider/budgets/shares/{share}/revoke     provider.budgets.shares.revoke
```

## Testes Implementados

Arquivo: `tests/Feature/BudgetShareNavigationTest.php`

Testes criados:
1. **Breadcrumb na página index**
2. **Breadcrumb na página dashboard**
3. **Breadcrumb na página create**
4. **Breadcrumb na página show**
5. **Rotas corretas em formulários e links**
6. **Fluxo de navegação completo**

## Manutenção e Extensão

### Para adicionar novas páginas:

1. Seguir a mesma estrutura de breadcrumb
2. Manter a consistência visual com `container-fluid py-1`
3. Usar ícones Bootstrap Icons apropriados
4. Adicionar testes correspondentes
5. Atualizar este documento

### Verificação de Qualidade

- ✅ Breadcrumbs em todas as páginas
- ✅ Hierarquia lógica e consistente
- ✅ Links funcionais e corretos
- ✅ Consistência visual com o restante da aplicação
- ✅ Testes automatizados
- ✅ Documentação atualizada

## Notas de Implementação

1. **Segurança**: Todas as rotas requerem autenticação e middleware `provider`
2. **Tenant Isolation**: Os dados são filtrados por `tenant_id`
3. **Paginação**: Lista de compartilhamentos usa paginação de 15 itens
4. **Responsividade**: Design responsivo para mobile e desktop
5. **Acessibilidade**: Uso de `aria-label` e `aria-current` para leitores de tela