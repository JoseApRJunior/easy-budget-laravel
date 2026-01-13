# Checklist de Migração e Revisão de Componentes

Este checklist monitora a migração das views para o padrão de componentes Blade e a revisão visual (padrão "Clean" e sem referências a tema escuro).

## 📊 Dashboards (Provider)
- [x] **Dashboard de Serviços** (`pages.service.dashboard`) - *Totalmente aprovado e revisado*
- [ ] **Dashboard de Orçamentos** (`pages.budget.dashboard`)
- [ ] **Dashboard Principal** (`provider.dashboard` / `home.dashboard.index`)
- [ ] **Dashboard de Clientes** (`pages.customer.dashboard`)
- [ ] **Dashboard de Produtos** (`pages.product.dashboard`)
- [ ] **Dashboard de Estoque** (`pages.inventory.dashboard`)
- [ ] **Dashboard Financeiro** (`pages.financial.dashboard`)
- [ ] **Dashboard de Faturas** (`pages.invoice.dashboard`)
- [ ] **Dashboard de Agenda** (`pages.schedule.dashboard`)
- [ ] **Dashboard de Categorias** (`pages.category.dashboard`)
- [ ] **Dashboard de Relatórios** (`pages.report.dashboard`)

## 📝 Módulos Principais (CRUDs)
### 📄 Orçamentos (Budgets)
- [ ] Listagem (`pages.budget.index`)
- [ ] Cadastro (`pages.budget.create`)
- [ ] Edição (`pages.budget.edit`)
- [ ] Visualização (`pages.budget.show`)
- [ ] Escolha de Status (`pages.budget.choose-status`)
- [ ] Compartilhamento (`pages.budget-share.*`)

### 🛠️ Serviços (Services)
- [ ] Listagem (`pages.service.index`)
- [ ] Cadastro (`pages.service.create`)
- [ ] Edição (`pages.service.edit`)
- [ ] Visualização (`pages.service.show`)

### 👥 Clientes (Customers)
- [ ] Listagem (`pages.customer.index`)
- [ ] Cadastro (`pages.customer.create`)
- [ ] Edição (`pages.customer.edit`)
- [ ] Visualização (`pages.customer.show`)
- [ ] Serviços e Orçamentos do Cliente (`pages.customer.services_and_quotes`)

### 📦 Produtos e Estoque (Products/Inventory)
- [ ] Listagem de Produtos (`pages.product.index`)
- [ ] Cadastro/Edição de Produto (`pages.product.create/edit`)
- [ ] Listagem de Estoque (`pages.inventory.index`)
- [ ] Movimentações (`pages.inventory.movements`)
- [ ] Ajuste de Estoque (`pages.inventory.adjust`)
- [ ] Alertas de Estoque (`pages.inventory.alerts`)

### 💰 Faturas (Invoices)
- [ ] Listagem (`pages.invoice.index`)
- [ ] Cadastro (`pages.invoice.create`)
- [ ] Visualização (`pages.invoice.show`)

### � Agenda (Schedule)
- [ ] Calendário (`pages.schedule.calendar`)
- [ ] Listagem (`pages.schedule.index`)
- [ ] Cadastro/Edição (`pages.schedule.create/edit`)

### 📂 Categorias e Unidades
- [ ] Categorias (`pages.category.index`)
- [ ] Unidades de Medida (`pages.unit.index`)

## 👑 Administrativo (Admin)
- [ ] Dashboard Admin (`pages.admin.dashboard`)
- [ ] Dashboard Executivo (`pages.admin.executive-dashboard`)
- [ ] Gestão de Usuários (`pages.admin.user.index`)
- [ ] Gestão de Tenants (`pages.admin.tenant.index`)
- [ ] Planos e Assinaturas (`pages.admin.plan.*`)
- [ ] Monitoramento e Logs (`pages.admin.monitoring.*` / `pages.admin.logs.*`)
- [ ] Configurações (`pages.admin.settings.index`)

## 🌍 Páginas Públicas e Perfil
- [ ] Perfil do Usuário (`pages.profile.edit`)
- [ ] Dados da Empresa (`pages.provider.business.edit`)
- [ ] Home/Landing Page (`pages.home.index`)
- [ ] Páginas Legais (Privacidade/Termos)

## 🎨 Componentes Base (Revisados)
- [x] `x-dashboard.stat-card`
- [x] `x-dashboard.insight-item`
- [x] `x-dashboard.chart-doughnut`
- [x] `x-layout.page-header`
- [x] `x-resource.resource-list-card`

---
*Legenda:*
- [x] Verificado e Aprovado
- [ ] Pendente Revisão/Migração
