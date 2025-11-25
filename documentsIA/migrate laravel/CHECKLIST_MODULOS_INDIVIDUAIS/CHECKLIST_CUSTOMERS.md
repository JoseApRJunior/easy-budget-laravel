# 📋 **CHECKLIST CUSTOMERS - MÓDULO INDIVIDUAL**

[⬅️ Voltar ao Índice](../INDICE_CHECKLISTS.md)

## 🎯 **Informações do Módulo:**

-  **Nome:** Customers (CRM)
-  **Dependências:** CommonData, Contact, Address
-  **Prioridade:** 🟥 CRÍTICA
-  **Impacto:** Alto (Budgets dependem de Customers)
-  **Status:** CRUD unificado PF/PJ implementado parcialmente

---

## 🔧 **BACKEND DEVELOPMENT**

### **📦 Models**

-  [x] Customer (app/Models/Customer.php)
   -  [x] Relacionamentos: commonData, contact, address
   -  [x] Escopos: por tenant

-  [x] CommonData, Contact, Address
   -  [x] Campos e validações

### **📂 Repository Pattern**

-  [x] CustomerRepository — filtros avançados
   -  [x] Busca por nome/email/CPF/CNPJ
   -  [ ] Segmentação por tags/status

### **🔧 Service Layer**

-  [x] CustomerService (app/Services/Domain/CustomerService.php)
   -  [x] create(), updateCustomer(), deleteCustomer()
   -  [x] getFilteredCustomers(), listCustomers()

---

## 🎮 **CONTROLLER & ROTAS**

### **🎯 CustomerController (app/Http/Controllers/CustomerController.php)**

-  [x] index() — listagem com filtros
-  [x] create() — formulário
-  [x] store() — criação unificada PF/PJ
-  [x] show(id) — detalhes
-  [x] edit(id) — edição
-  [x] update() — atualização
-  [x] destroy() — exclusão

### **🛣️ Rotas (routes/web.php)**

-  [x] Grupo `provider.customers.*`
-  [x] Rotas RESTful completas

---

## 🎨 **FRONTEND INTERFACE**

### **📁 Views (resources/views/pages/customer/)**

-  [x] index.blade.php — listagem
-  [x] create.blade.php — criação PF/PJ
-  [x] edit.blade.php — edição
-  [x] show.blade.php — detalhes
-  [x] dashboard.blade.php — métricas

---

## 🧪 **TESTING**

-  [ ] Factories/Seeders
-  [ ] Unit: CustomerService
-  [ ] Feature: CustomerController

---

## ✅ **VALIDAÇÃO FINAL**

-  [ ] CRUD completo PF/PJ
-  [ ] Busca/segmentação funcionais
-  [ ] Integração CommonData/Contact/Address

---

## 🚨 **CHECKLIST DE DEPLOY**

-  [ ] Migrations e seeders
-  [ ] Cache/config otimizados
-  [ ] Testes passando

---

## 📊 **MÉTRICAS DE SUCESSO**

-  [ ] Tempo de resposta <2s
-  [ ] Zero erros críticos
-  [ ] Integração com Budgets
