# Matriz de Priorização - Migração Easy Budget

## 🎯 Critérios de Priorização

### Fatores de Impacto
1. **Criticidade de Negócio** (1-5)
2. **Frequência de Uso** (1-5) 
3. **Complexidade Técnica** (1-5)
4. **Dependências** (1-5)
5. **Impacto no Usuário** (1-5)

### Fórmula de Prioridade
```
Prioridade = (Criticidade × 0.3) + (Frequência × 0.25) + (Impacto_Usuario × 0.25) + (Dependências × 0.15) - (Complexidade × 0.05)
```

## 📊 Matriz de Arquivos Prioritários

### 🔴 PRIORIDADE CRÍTICA (Score: 4.0-5.0)

| Arquivo | Criticidade | Frequência | Complexidade | Dependências | Impacto | Score | Fase |
|---------|-------------|------------|--------------|--------------|---------|-------|------|
| `layout.twig` | 5 | 5 | 3 | 5 | 5 | **4.85** | 1 |
| `home/index.twig` | 5 | 5 | 2 | 4 | 5 | **4.75** | 1 |
| `login/index.twig` | 5 | 4 | 2 | 3 | 5 | **4.45** | 1 |
| `partials/shared/navigation.twig` | 4 | 5 | 3 | 5 | 4 | **4.40** | 1 |
| `partials/shared/head.twig` | 4 | 5 | 2 | 5 | 4 | **4.35** | 1 |

### 🟠 PRIORIDADE ALTA (Score: 3.0-3.9)

| Arquivo | Criticidade | Frequência | Complexidade | Dependências | Impacto | Score | Fase |
|---------|-------------|------------|--------------|--------------|---------|-------|------|
| `budget/index.twig` | 5 | 4 | 3 | 3 | 4 | **3.95** | 2 |
| `customer/index.twig` | 4 | 4 | 3 | 3 | 4 | **3.75** | 2 |
| `admin/dashboard.twig` | 4 | 3 | 3 | 3 | 4 | **3.55** | 2 |
| `budget/create.twig` | 4 | 4 | 4 | 2 | 4 | **3.50** | 3 |
| `service/index.twig` | 4 | 3 | 3 | 3 | 4 | **3.45** | 3 |
| `invoice/index.twig` | 4 | 3 | 3 | 3 | 4 | **3.45** | 3 |
| `macros/alerts.twig` | 3 | 5 | 2 | 4 | 3 | **3.40** | 1 |
| `partials/components/breadcrumbs.twig` | 3 | 4 | 2 | 4 | 3 | **3.25** | 1 |

### 🟡 PRIORIDADE MÉDIA (Score: 2.0-2.9)

| Arquivo | Criticidade | Frequência | Complexidade | Dependências | Impacto | Score | Fase |
|---------|-------------|------------|--------------|--------------|---------|-------|------|
| `budget/show.twig` | 4 | 3 | 3 | 2 | 3 | **2.95** | 3 |
| `customer/create.twig` | 3 | 3 | 4 | 2 | 3 | **2.85** | 3 |
| `service/create.twig` | 3 | 3 | 4 | 2 | 3 | **2.85** | 3 |
| `product/index.twig` | 3 | 3 | 3 | 2 | 3 | **2.75** | 3 |
| `invoice/create.twig` | 3 | 2 | 4 | 2 | 3 | **2.65** | 4 |
| `report/index.twig` | 3 | 2 | 3 | 2 | 3 | **2.55** | 5 |
| `settings/index.twig` | 2 | 2 | 3 | 2 | 3 | **2.25** | 4 |

### 🟢 PRIORIDADE BAIXA (Score: 1.0-1.9)

| Arquivo | Criticidade | Frequência | Complexidade | Dependências | Impacto | Score | Fase |
|---------|-------------|------------|--------------|--------------|---------|-------|------|
| `legal/privacy_policy.twig` | 2 | 1 | 2 | 1 | 2 | **1.75** | 6 |
| `legal/terms_of_service.twig` | 2 | 1 | 2 | 1 | 2 | **1.75** | 6 |
| `error/notFound.twig` | 2 | 1 | 2 | 1 | 2 | **1.75** | 6 |
| `development/index.twig` | 1 | 1 | 2 | 1 | 1 | **1.25** | 6 |

## 📋 Plano de Execução Detalhado

### Fase 1: Infraestrutura (Semana 1-2)
**Objetivo**: Estabelecer base sólida para migração

#### Ordem de Execução:
1. **layout.twig** → `resources/views/layouts/app.blade.php`
2. **partials/shared/head.twig** → `resources/views/layouts/partials/head.blade.php`
3. **partials/shared/navigation.twig** → `resources/views/components/navigation.blade.php`
4. **macros/alerts.twig** → `resources/views/components/alert.blade.php`
5. **partials/components/breadcrumbs.twig** → `resources/views/components/breadcrumb.blade.php`

#### Configurações Paralelas:
- Setup Vite configuration
- TailwindCSS installation e configuração
- Blade components registration

### Fase 2: Páginas Principais (Semana 3-4)
**Objetivo**: Migrar páginas mais acessadas

#### Ordem de Execução:
1. **home/index.twig** → `resources/views/dashboard/index.blade.php`
2. **login/index.twig** → `resources/views/auth/login.blade.php`
3. **budget/index.twig** → `resources/views/budgets/index.blade.php`
4. **customer/index.twig** → `resources/views/customers/index.blade.php`
5. **admin/dashboard.twig** → `resources/views/admin/dashboard.blade.php`

### Fase 3: Módulos de Negócio (Semana 5-8)
**Objetivo**: Funcionalidades core do sistema

#### Semana 5-6: Orçamentos e Clientes
1. **budget/create.twig** → `resources/views/budgets/create.blade.php`
2. **budget/show.twig** → `resources/views/budgets/show.blade.php`
3. **customer/create.twig** → `resources/views/customers/create.blade.php`
4. **customer/show.twig** → `resources/views/customers/show.blade.php`

#### Semana 7-8: Serviços e Produtos
1. **service/index.twig** → `resources/views/services/index.blade.php`
2. **service/create.twig** → `resources/views/services/create.blade.php`
3. **product/index.twig** → `resources/views/products/index.blade.php`
4. **product/create.twig** → `resources/views/products/create.blade.php`

## 🎨 Estratégia de Conversão CSS

### Mapeamento de Classes Prioritárias

#### Layout Base
```css
/* OLD: layout.css */
.container { max-width: 1200px; margin: 0 auto; }
.header { background: #f8f9fa; padding: 1rem; }
.sidebar { width: 250px; background: #343a40; }

/* NEW: TailwindCSS */
.container → max-w-6xl mx-auto
.header → bg-gray-50 p-4
.sidebar → w-64 bg-gray-800
```

#### Componentes
```css
/* Alerts */
.alert-success → bg-green-100 border-green-400 text-green-700
.alert-error → bg-red-100 border-red-400 text-red-700
.alert-warning → bg-yellow-100 border-yellow-400 text-yellow-700

/* Buttons */
.btn-primary → bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded
.btn-secondary → bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded
```

## 🔧 Assets JavaScript

### Módulos por Prioridade

#### Críticos (Fase 1)
- `modules/utils.js` → Core utilities
- `modules/form-validation.js` → Form handling
- `alert/alert.js` → Alert system

#### Altos (Fase 2-3)
- `budget.js` → Budget functionality
- `customer.js` → Customer management
- `modules/masks/` → Input masks

#### Médios (Fase 4-5)
- `invoice.js` → Invoice handling
- `service.js` → Service management
- `monitoring.js` → Admin monitoring

## 📊 Métricas de Acompanhamento

### KPIs por Fase
- **Fase 1**: 5 arquivos base migrados
- **Fase 2**: 10 páginas principais funcionais
- **Fase 3**: 20 módulos de negócio completos
- **Fase 4**: 30 funcionalidades financeiras
- **Fase 5**: 40 relatórios e admin
- **Fase 6**: 50+ arquivos totais migrados

### Critérios de Aceitação
- ✅ Visual idêntico ao original
- ✅ Funcionalidade 100% preservada
- ✅ Performance igual ou superior
- ✅ Responsividade mantida
- ✅ Testes passando

## 🚨 Pontos de Atenção

### Arquivos Complexos
- **budget/pdf_budget.twig**: Geração de PDF complexa
- **invoice/pdf_invoice_print.twig**: Layout específico para impressão
- **admin/metrics-dashboard.twig**: Gráficos e dashboards interativos

### Dependências Críticas
- Sistema de multi-tenancy
- Integração MercadoPago
- Sistema de permissões
- Notificações em tempo real

---

**Status**: Documentação completa ✅  
**Próximo Passo**: Iniciar Fase 1 - Configuração Vite e migração do layout base