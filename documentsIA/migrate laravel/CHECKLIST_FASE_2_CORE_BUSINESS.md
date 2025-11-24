# 📋 **CHECKLIST FASE 2 - CORE BUSINESS (Semanas 3-5)**

## 🎯 **Objetivo:** Implementar funcionalidades centrais do sistema

### **Status Geral da Fase:**

-  **Prazo:** Semanas 3-5
-  **Prioridade:** CRÍTICA
-  **Impacto:** 🟥 CRÍTICO - Funcionalidades principais do sistema
-  **Pré-requisitos:** FASE 1 100% concluída

---

## 💰 **4. BUDGETS (ORÇAMENTOS) - PRIORIDADE CRÍTICA**

### **📊 Informações do Módulo:**

-  **Status Atual:** 3/12 métodos implementados
-  **Dependências:** Customers (obrigatório)
-  **Impacto:** 🟥 CRÍTICO - Funcionalidade central do sistema
-  **Tempo Estimado:** 10 dias

### **✅ Checklist de Desenvolvimento:**

#### **🔧 Backend (Models, Repositories, Services)**

-  [ ] Verificar e atualizar Budget Model

   -  [ ] Relationships corretas (customer, user, status)
   -  [ ] Fillable/casts adequados
   -  [ ] Traits TenantScoped e Auditable
   -  [ ] PDF generation support

-  [ ] Implementar BudgetRepository completo

   -  [ ] Interface definida
   -  [ ] CRUD completo
   -  [ ] Busca por customer/status/datas
   -  [ ] Filtros avançados
   -  [ ] Relatórios financeiros

-  [ ] Implementar BudgetService
   -  [ ] ServiceResult padronizado
   -  [ ] Cálculos de totais e descontos
   -  [ ] Geração de códigos únicos
   -  [ ] Validações de negócio
   -  [ ] Workflow de aprovação

#### **🎮 Controller - Implementar Métodos Faltantes**

##### **Métodos CRUD Básicos:**

-  [ ] **create()** - Exibir formulário

   -  [ ] Carregar lista de customers (dropdown)
   -  [ ] Carregar lista de services (se houver)
   -  [ ] Formulário de criação
   -  [ ] Validações client-side

-  [ ] **store()** - Criar orçamento

   -  [ ] Validação de dados
   -  [ ] Verificar customer_id obrigatório
   -  [ ] Gerar código único
   -  [ ] Calcular totais
   -  [ ] Salvar no banco
   -  [ ] Log de auditoria

-  [ ] **show()** - Visualizar orçamento

   -  [ ] Detalhamento completo
   -  [ ] Services relacionados
   -  [ ] Histórico de alterações
   -  [ ] Botões de ação
   -  [ ] Gerar PDF

-  [ ] **edit()** - Editar orçamento

   -  [ ] Carregar dados existentes
   -  [ ] Formulário de edição
   -  [ ] Manter histórico

-  [ ] **update()** - Atualizar orçamento
   -  [ ] Validação de dados
   -  [ ] Verificar permissões
   -  [ ] Salvar alterações
   -  [ ] Log de auditoria

##### **Métodos de Workflow:**

-  [ ] **change_status()** - Mudar status

   -  [ ] Workflow approval
   -  [ ] Validações por status
   -  [ ] Notificações (se aplicável)

-  [ ] **choose_budget_status_store()** - Aprovação pelo cliente
   -  [ ] Interface pública
   -  [ ] Validação de token
   -  [ ] Aprovação/reprovação
   -  [ ] Confirmação por email

#### **🎨 Interface (Views)**

-  [ ] Criar/atualizar views em resources/views/pages/budget/
   -  [ ] index.blade.php - listagem com filtros
   -  [ ] create.blade.php - formulário de criação
   -  [ ] show.blade.php - visualização detalhada
   -  [ ] edit.blade.php - formulário de edição
   -  [ ] partials para filtros e ações

#### **📄 PDF Generation**

-  [ ] Implementar Budget PDF
   -  [ ] Layout profissional
   -  [ ] Logo da empresa
   -  [ ] Dados do customer
   -  [ ] Services/itens
   -  [ ] Totais e condições
   -  [ ] Download/email

#### **🔐 Sistema de Tokens Públicos**

-  [ ] Implementar tokens para aprovação
   -  [ ] Geração de tokens únicos
   -  [ ] Expiração automática
   -  [ ] Interface pública para aprovação
   -  [ ] Validação de tokens

#### **🧪 Testes**

-  [ ] Criar BudgetFactory
-  [ ] Implementar BudgetSeeder
-  [ ] Testes unitários BudgetService
-  [ ] Testes de Feature BudgetController
-  [ ] Testes de PDF generation
-  [ ] Testes de workflow approval

#### **✅ Validação Final Budgets**

-  [ ] Todos os 12 métodos funcionando
-  [ ] PDF generation funcionando
-  [ ] Workflow de aprovação operacional
-  [ ] Interface responsiva
-  [ ] Integração com Customers 100%

---

## 🛠️ **5. SERVICES (SERVIÇOS) - PRIORIDADE CRÍTICA**

### **📊 Informações do Módulo:**

-  **Status Atual:** Controller existe, funcionalidade limitada
-  **Dependências:** Budgets + Customers + Categories (todos obrigatórios)
-  **Impacto:** 🟥 CRÍTICO - Integração direta com orçamentos
-  **Tempo Estimado:** 12 dias

### **✅ Checklist de Desenvolvimento:**

#### **🔧 Backend (Models, Repositories, Services)**

-  [ ] Verificar e atualizar Service Model

   -  [ ] Relationships corretas (budget, category, items)
   -  [ ] Fillable/casts adequados
   -  [ ] Traits TenantScoped e Auditable
   -  [ ] PDF generation support

-  [ ] Implementar ServiceRepository completo

   -  [ ] Interface definida
   -  [ ] CRUD completo
   -  [ ] Busca por budget/category/status
   -  [ ] Filtros avançados
   -  [ ] Relatórios de performance

-  [ ] Implementar ServiceService
   -  [ ] ServiceResult padronizado
   -  [ ] Cálculos de totais
   -  [ ] Gestão de ServiceItems
   -  [ ] Validações específicas
   -  [ ] Workflow de execução

#### **🎮 Controller - CRUD Completo**

##### **Métodos CRUD Básicos:**

-  [ ] **create()** - Exibir formulário

   -  [ ] Carregar lista de budgets (dropdown)
   -  [ ] Carregar lista de categories (dropdown)
   -  [ ] Carregar lista de products (para itens)
   -  [ ] Formulário de criação

-  [ ] **store()** - Criar serviço

   -  [ ] Validação de dados
   -  [ ] Verificar budget_id obrigatório
   -  [ ] Verificar category_id obrigatório
   -  [ ] Criar ServiceItems
   -  [ ] Calcular totais

-  [ ] **show()** - Visualizar serviço

   -  [ ] Detalhamento completo
   -  [ ] ServiceItems relacionados
   -  [ ] Budget relacionado
   -  [ ] Histórico de alterações

-  [ ] **edit()** - Editar serviço

   -  [ ] Carregar dados existentes
   -  [ ] Formulário de edição
   -  [ ] Gestão de itens

-  [ ] **update()** - Atualizar serviço
   -  [ ] Validação de dados
   -  [ ] Atualizar ServiceItems
   -  [ ] Recalcular totais
   -  [ ] Log de auditoria

#### **📦 ServiceItems Management**

-  [ ] Implementar ServiceItem controller/methods
   -  [ ] Adicionar produtos ao serviço
   -  [ ] Remover produtos do serviço
   -  [ ] Editar quantidades e valores
   -  [ ] Recálculo automático de totais

#### **🎨 Interface (Views)**

-  [ ] Criar/atualizar views em resources/views/pages/service/
   -  [ ] index.blade.php - listagem com filtros
   -  [ ] create.blade.php - formulário de criação
   -  [ ] show.blade.php - visualização detalhada
   -  [ ] edit.blade.php - formulário de edição
   -  [ ] Componentes para ServiceItems

#### **🔗 Integrações Críticas**

-  [ ] **Integration com Budgets**

   -  [ ] Carregar budget na criação
   -  [ ] Vincular serviço ao budget
   -  [ ] Atualizar totais do budget
   -  [ ] Status sync

-  [ ] **Integration com Categories**

   -  [ ] Carregar categories no dropdown
   -  [ ] Validação obrigatória
   -  [ ] Descrições padrão por categoria

-  [ ] **Integration com Products**
   -  [ ] Carregar produtos disponíveis
   -  [ ] Preços automáticos
   -  [ ] Gestão de estoque
   -  [ ] ServiceItems dinâmicos

#### **📄 PDF Generation**

-  [ ] Implementar Service PDF
   -  [ ] Layout específico para serviços
   -  [ ] Detalhamento de itens
   -  [ ] Condições de execução
   -  [ ] Timeline do projeto

#### **🧪 Testes**

-  [ ] Criar ServiceFactory
-  [ ] Implementar ServiceSeeder
-  [ ] Testes unitários ServiceService
-  [ ] Testes de Feature ServiceController
-  [ ] Testes de ServiceItems
-  [ ] Testes de integração

#### **✅ Validação Final Services**

-  [ ] CRUD completo funcionando
-  [ ] ServiceItems management 100%
-  [ ] Integração com Budgets operacional
-  [ ] Integração com Categories/Products 100%
-  [ ] PDF generation funcionando
-  [ ] Interface completa e responsiva

---

## ✅ **CRITÉRIOS DE CONCLUSÃO DA FASE 2**

### **🎯 Validação Técnica:**

-  [ ] Budgets: Todos os 12 métodos funcionando
-  [ ] Services: CRUD completo + ServiceItems
-  [ ] Integrações entre módulos funcionais
-  [ ] PDF generation operacional
-  [ ] Testes passando (>90% cobertura)

### **🎯 Validação de Negócio:**

-  [ ] Usuário pode criar orçamentos completos
-  [ ] Usuário pode adicionar serviços aos orçamentos
-  [ ] Usuário pode aprovar/reprovar orçamentos
-  [ ] Workflow de aprovação funcionando
-  [ ] Sistema pronto para faturamento

### **🎯 Valor para o Usuário:**

-  [ ] Sistema de orçamentos 100% funcional
-  [ ] Gestão completa de serviços
-  [ ] Fluxo de aprovação operacional
-  [ ] Interface profissional e intuitiva
-  [ ] Pronto para faturamento

---

## 🚨 **ALERTAS E RISCOS**

### **⚠️ Dependências Críticas:**

-  **Budgets** depende 100% de Customers da Fase 1
-  **Services** depende de Budgets + Categories + Products da Fase 1

### **🔍 Pontos de Atenção:**

-  Workflow de aprovação pode ser complexo
-  ServiceItems precisam de interface intuitiva
-  PDFs devem ser profissionais
-  Performance com muitos itens

### **📞 Escalação:**

Se qualquer integração não estiver funcionando, **PARAR** e corrigir antes de partir para Fase 3.

---

**✅ Prévia Fase:** [CHECKLIST_FASE_1_BASE_FUNCIONAL.md](./CHECKLIST_FASE_1_BASE_FUNCIONAL.md)
**✅ Próxima Fase:** [CHECKLIST_FASE_3_FLUXO_FINANCEIRO.md](./CHECKLIST_FASE_3_FLUXO_FINANCEIRO.md)
