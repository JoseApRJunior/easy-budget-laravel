# Checklist de Migração e Revisão de Componentes

Este checklist monitora a migração das views para o padrão de componentes Blade e a revisão visual (padrão "Clean" e sem referências a tema escuro).

## 📊 Dashboards (Provider)
- [x] **Dashboard de Serviços** (`pages.service.dashboard`) - *Totalmente aprovado e revisado*
- [x] **Dashboard de Orçamentos** (`pages.budget.dashboard`) - *Totalmente aprovado e revisado*
- [x] **Dashboard Principal** (`provider.dashboard` / `home.dashboard.index`) - *Totalmente aprovado e revisado*
- [x] **Dashboard de Clientes** (`pages.customer.dashboard`) - *Totalmente aprovado e revisado*
- [x] **Dashboard de Produtos** (`pages.product.dashboard`) - *Totalmente aprovado e revisado*
- [x] **Dashboard de Estoque** (`pages.inventory.dashboard`) - *Totalmente aprovado e revisado*
- [x] **Dashboard Financeiro** (`pages.financial.dashboard`) - *Totalmente aprovado e revisado*
- [x] **Dashboard de Faturas** (`pages.invoice.dashboard`) - *Totalmente aprovado e revisado*
- [x] **Dashboard de Agenda** (`pages.schedule.dashboard`) - *Totalmente aprovado e revisado*
- [x] **Dashboard de Categorias** (`pages.category.dashboard`) - *Totalmente aprovado e revisado*
- [x] **Dashboard de Relatórios** (`pages.report.dashboard`) - *Totalmente aprovado e revisado*

## 📝 Módulos Principais (CRUDs)
### 📄 Orçamentos (Budgets)
- [x] Listagem (`pages.budget.index`)
- [x] Cadastro (`pages.budget.create`)
- [x] Edição (`pages.budget.edit`)
- [x] Visualização (`pages.budget.show`)
- [x] Escolha de Status (`pages.budget.choose-status`)
- [x] Compartilhamento (`pages.budget-share.*`)

### 🛠️ Serviços (Services)
- [x] Listagem (`pages.service.index`)
- [x] Cadastro (`pages.service.create`)
- [x] Edição (`pages.service.edit`)
- [x] Visualização (`pages.service.show`)

### 👥 Clientes (Customers)
- [x] Listagem (`pages.customer.index`)
- [x] Cadastro (`pages.customer.create`)
- [x] Edição (`pages.customer.edit`)
- [x] Visualização (`pages.customer.show`)
- [x] Serviços e Orçamentos do Cliente (`pages.customer.services_and_quotes`)

### 📦 Produtos e Estoque (Products/Inventory)
- [x] Listagem de Produtos (`pages.product.index`)
- [x] Cadastro/Edição de Produto (`pages.product.create/edit`)
- [x] Listagem de Estoque (`pages.inventory.index`)
- [x] Movimentações (`pages.inventory.movements`)
- [x] Ajuste de Estoque (`pages.inventory.adjust`)
- [x] Alertas de Estoque (`pages.inventory.alerts`)

### 💰 Faturas (Invoices)
- [x] Listagem (`pages.invoice.index`)
- [x] Cadastro (`pages.invoice.create`)
- [x] Edição (`pages.invoice.edit`)
- [x] Visualização (`pages.invoice.show`)

### 📅 Agenda (Schedule)
- [x] Calendário (`pages.schedule.calendar`)
- [x] Listagem (`pages.schedule.index`)
- [x] Cadastro/Edição (`pages.schedule.create/edit`)

### 📂 Categorias e Unidades
- [x] Categorias (`pages.category.index`)
- [x] Unidades de Medida (`pages.unit.index`)

## 👑 Administrativo (Admin)
- [x] Dashboard Admin (`pages.admin.dashboard`)
- [x] Dashboard Executivo (`pages.admin.executive-dashboard`)
- [x] Gestão de Usuários (`pages.admin.user.index`)
- [x] Gestão de Tenants (`pages.admin.tenant.index`)
- [x] Planos e Assinaturas (`pages.admin.plan.*`)
- [x] Monitoramento e Logs (`pages.admin.monitoring.*` / `pages.admin.logs.*`)
- [x] Configurações (`pages.admin.settings.index`)
- [x] Profissões (`pages.profession.index`)
- [x] Áreas de Atividade (`pages.area-of-activity.index`)
- [x] Funções (`pages.role.index`)

## 🌍 Páginas Públicas e Perfil
- [x] Perfil do Usuário (`pages.profile.edit`)
- [x] Dados da Empresa (`pages.provider.business.edit`)
- [x] Landing Page (`home.*` / `welcome.blade.php`)
- [x] Páginas Legais (`pages.legal.*`)
- [x] Páginas de Erro (`pages.error.*`)
- [x] Verificação de Documentos (`pages.document.verify`)

## 🔐 Autenticação e Layouts
- [x] Layout Principal (`layouts.app`)
- [x] Views de Autenticação (`auth.*`)
- [x] Headers e Footers (`partials.shared.*`)

## 💳 Pagamentos e Integrações
- [x] Integração Mercado Pago (`pages.mercadopago.index`)
- [x] Redirecionamento de Pagamento (`pages.payment.*`)
- [x] Status de Pagamento de Planos (`pages.plan.status` / `payment-status`)

## 🎨 Componentes Base (Revisados)
- [x] `x-dashboard.stat-card`
- [x] `x-dashboard.insight-item`
- [x] `x-dashboard.chart-doughnut`
- [x] `x-layout.page-header`
- [x] `x-resource.resource-list-card`

---
**Status Final:** ✅ Todos os componentes e páginas principais foram migrados e revisados.
