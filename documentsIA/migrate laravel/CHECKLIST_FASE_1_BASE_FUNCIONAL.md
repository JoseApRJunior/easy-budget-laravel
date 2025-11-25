# 📋 **CHECKLIST FASE 1 - BASE FUNCIONAL (Semanas 1-2)**

[⬅️ Voltar ao Índice](./INDICE_CHECKLISTS.md)

## 🎯 **Objetivo:** Estabelecer fundações sólidas para todo o sistema

### **Status Geral da Fase:**

-  **Prazo:** Semanas 1-2
-  **Prioridade:** MÁXIMA
-  **Impacto:** CRÍTICO - Estas funcionalidades desbloqueiam todo o resto

---

## 📂 **1. CATEGORIES (PRIORIDADE MÁXIMA) — Pivot, Default, Auditoria, Gates**

### **📊 Informações do Módulo:**

-  **Status Atual:** Schema unificado, Model/Repository atualizados, Views prontas
-  **Dependências:** Nenhuma (independente)
-  **Impacto:** 🟨 ALTO - Services e Products precisam de categories
-  **Tempo Estimado:** 3 dias

### **✅ Checklist de Desenvolvimento:**

#### **🔧 Backend (Models, Repositories, Services)**

-  [x] Verificar e atualizar Category Model

   -  [x] Relationships corretas
   -  [x] Fillable/casts adequados
   -  [x] Auditable
   -  [x] TenantScoped (N/A para Category — usa pivot)

-  [x] Implementar CategoryRepository

   -  [x] Interface (N/A — usa AbstractGlobalRepository com GlobalRepositoryInterface)
   -  [x] Métodos CRUD completos
-  [x] Filtros e busca
-  [x] Validações de negócio

-  [x] Implementar CategoryService
   -  [x] ServiceResult em todas operações
   -  [x] Validações específicas
   -  [x] Regras de negócio

#### **🎮 Controller e Rotas**

-  [x] Implementar CategoryController completo

   -  [x] index() - listagem com paginação
   -  [x] create() - formulário de criação
   -  [x] store() - validação e criação
   -  [x] show() - visualização individual
   -  [x] edit() - formulário de edição
   -  [x] update() - validação e atualização
   -  [ ] destroy() - exclusão segura

-  [x] Verificar rotas em routes/web.php
   -  [x] Rotas RESTful configuradas
   -  [x] Middleware aplicado
   -  [x] Nomes de rotas consistentes

#### **🎨 Interface (Views)**

-  [x] Criar/atualizar views em resources/views/pages/category/
   -  [x] index.blade.php - listagem com search/filter
   -  [x] create.blade.php - formulário de criação
   -  [x] edit.blade.php - formulário de edição
   -  [x] show.blade.php - visualização detalhada
   -  [ ] partials para filtros e ações

#### **🧪 Testes**

-  [x] Criar CategoryFactory
-  [x] Implementar CategorySeeder
-  [ ] Testes unitários CategoryService
-  [x] Testes de Feature CategoryController
-  [ ] Testes de integração com UI

#### **✅ Validação Final**

-  [ ] CRUD funcionando completamente
-  [ ] Validações client-side e server-side
-  [ ] Responsividade testada
-  [ ] Performance adequada
-  [ ] Sem dependências quebradas

---

## 📦 **2. PRODUCTS (PRIORIDADE MÁXIMA)**

### **📊 Informações do Módulo:**

-  **Status Atual:** Estrutura existe, funcionalidades limitadas
-  **Dependências:** Nenhuma (independente)
-  **Impacto:** 🟨 ALTO - Base para precificação de serviços
-  **Tempo Estimado:** 4 dias

### **✅ Checklist de Desenvolvimento:**

#### **🔧 Backend (Models, Repositories, Services)**

-  [x] Verificar e atualizar Product Model

   -  [x] Relationships corretas (category, inventory)
   -  [x] Fillable/casts adequados
   -  [ ] Traits TenantScoped e Auditable

-  [x] Implementar ProductRepository

   -  [ ] Interface definida
   -  [x] CRUD completo
   -  [x] Busca por categoria/preço
   -  [x] Filtros avançados

-  [x] Implementar ProductService
   -  [ ] ServiceResult padronizado
   -  [ ] Gestão de estoque (ProductInventory)
   -  [ ] Gestão de preços
   -  [ ] Validações de negócio

#### **🎮 Controller e Rotas**

-  [x] Implementar ProductController completo

   -  [x] index() - listagem com filtros
   -  [x] create() - formulário de criação
   -  [x] store() - validação e criação
   -  [x] show() - visualização individual
   -  [x] edit() - formulário de edição
   -  [x] update() - validação e atualização
   -  [x] destroy() - exclusão segura

-  [x] Verificar/ajustar rotas em routes/web.php
   -  [x] Rotas RESTful
   -  [x] Middleware de autenticação
   -  [x] Namespacing adequado

#### **📦 Gestão de Inventário**

-  [ ] Implementar ProductInventory controller
   -  [ ] Adicionar estoque
   -  [ ] Remover estoque
   -  [x] Histórico de movimentações
   -  [ ] Alertas de estoque mínimo

#### **🎨 Interface (Views)**

-  [x] Criar/atualizar views em resources/views/pages/product/
   -  [x] index.blade.php - listagem com search/filter
   -  [x] create.blade.php - formulário de criação
   -  [x] edit.blade.php - formulário de edição
   -  [x] show.blade.php - visualização detalhada
   -  [x] dashboard.blade.php - visão geral do inventário
   -  [ ] Componentes para gestão de estoque

#### **🧪 Testes**

-  [x] Criar ProductFactory
-  [x] Implementar ProductSeeder
-  [ ] Testes unitários ProductService
-  [ ] Testes de Feature ProductController
-  [ ] Testes de gestão de estoque
-  [ ] Testes de integração UI

#### **✅ Validação Final**

-  [ ] CRUD de produtos funcionando
-  [ ] Gestão de estoque operacional
-  [ ] Filtros e busca eficientes
-  [ ] Interface responsiva
-  [ ] Integração pronta para ServiceItem

---

## 👥 **3. CUSTOMERS (PRIORIDADE CRÍTICA)**

### **📊 Informações do Módulo:**

-  **Status Atual:** Interface existe, funcionalidades básicas
-  **Dependências:** Nenhuma (independente)
-  **Impacto:** 🟥 CRÍTICO - Budgets não funcionam sem customers
-  **Tempo Estimado:** 7 dias

### **✅ Checklist de Desenvolvimento:**

#### **🔧 Backend (Models, Repositories, Services)**

-  [x] Verificar e atualizar Customer Model

   -  [x] Relationships (common_data, contact, address)
   -  [ ] Fillable/casts adequados
   -  [ ] Traits TenantScoped e Auditable

-  [x] Implementar CustomerRepository

   -  [ ] Interface definida
   -  [x] CRUD completo (PF/PJ)
   -  [x] Busca por tipo/nome/email
   -  [ ] Filtros avançados
   -  [ ] Relatórios básicos

-  [x] Implementar CustomerService
   -  [ ] ServiceResult padronizado
   -  [ ] Lógica para PF vs PJ
   -  [ ] Validações específicas
   -  [ ] Gerenciamento de dados relacionados

#### **🎮 Controller e Rotas**

-  [x] Implementar CustomerController completo
   -  [x] index() - listagem com paginação
   -  [x] create() - formulário de criação
   -  [x] store() - validação e criação
   -  [x] show() - visualização detalhada
   -  [x] edit() - formulário de edição
   -  [x] update() - validação e atualização
   -  [x] destroy() - exclusão segura
   -  [ ] services_and_quotes() - histórico de serviços

#### **🎨 Interface (Views)**

-  [x] Criar/atualizar views em resources/views/pages/customer/
   -  [x] index.blade.php - listagem com busca
   -  [x] create.blade.php - formulário PF/PJ
   -  [x] edit.blade.php - formulário de edição
   -  [x] show.blade.php - perfil completo
   -  [x] services_and_quotes.blade.php - histórico
   -  [x] dashboard.blade.php - visão geral CRM

#### **🔗 Integração com Dados Relacionados**

-  [ ] CommonData integration

   -  [ ] PF (CPF, nome, data nascimento)
   -  [ ] PJ (CNPJ, Razão Social, área atividade)
   -  [ ] Formulários dinâmicos

-  [ ] Contact integration

   -  [ ] Email principal/secundário
   -  [ ] Telefone principal/comercial
   -  [ ] Website

-  [ ] Address integration
   -  [ ] Endereço principal completo
   -  [ ] Validação de CEP
   -  [ ] Múltiplos endereços (futuro)

#### **📊 CRM e Segmentação**

-  [ ] Implementar Customer segmentation
   -  [ ] Por tipo (PF/PJ)
   -  [ ] Por região (cidade/estado)
   -  [ ] Por atividade econômica
   -  [ ] Por status (ativo/inativo)

#### **🧪 Testes**

-  [x] Criar CustomerFactory
-  [x] Implementar CustomerSeeder
-  [ ] Testes unitários CustomerService
-  [ ] Testes de Feature CustomerController
-  [ ] Testes de formulários dinâmicos
-  [ ] Testes de integração de dados

#### **✅ Validação Final**

-  [ ] CRUD completo funcionando
-  [ ] Formulários PF/PJ funcionais
-  [ ] Dados relacionados integrados
-  [ ] Busca e filtros operacionais
-  [ ] Interface CRM completa
-  [ ] Pronto para integração com Budgets

---

## ✅ **CRITÉRIOS DE CONCLUSÃO DA FASE 1**

### **🎯 Validação Técnica:**

-  [ ] Todos os CRUDs funcionam 100%
-  [ ] Testes passando (>90% cobertura)
-  [ ] Performance adequada (<2s loading)
-  [ ] Interface responsiva completa
-  [ ] Nenhuma dependência quebrada

### **🎯 Validação de Negócio:**

-  [ ] Usuário pode cadastrar categories
-  [ ] Usuário pode gerenciar produtos/estoque
-  [ ] Usuário pode gerenciar customers (PF/PJ)
-  [ ] Sistema prontos para próximos módulos

### **🎯 Valor para o Usuário:**

-  [ ] Base sólida estabelecida
-  [ ] Sistema funcional para gestão básica
-  [ ] Pronto para receber orçamentos
-  [ ] Interface profissional e intuitiva

---

## 🚨 **ALERTAS E RISCOS**

### **⚠️ Dependências Críticas:**

-  **Categories** deve ser 100% funcional antes de Services
-  **Products** deve ter gestão de estoque antes de Services
-  **Customers** deve ter CRUD completo antes de Budgets

### **🔍 Pontos de Atenção:**

-  Validação de CPF/CNPJ
-  Gestão de estoque em tempo real
-  Performance com muitos customers
-  Interface responsiva mobile

### **📞 Escalação:**

Se qualquer módulo da Fase 1 não estiver funcionando até o final da Semana 2, **PARAR** e corrigir antes de partir para Fase 2.

---

**✅ Próxima Fase:** [CHECKLIST_FASE_2_CORE_BUSINESS.md](./CHECKLIST_FASE_2_CORE_BUSINESS.md)
