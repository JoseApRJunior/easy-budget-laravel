# 📋 **CHECKLIST BUDGETS - MÓDULO INDIVIDUAL**

[⬅️ Voltar ao Índice](../INDICE_CHECKLISTS.md)

## 🎯 **Informações do Módulo:**

-  **Nome:** Budgets (Orçamentos)
-  **Dependências:** Customers, Services, Templates, Tokens Públicos
-  **Prioridade:** 🟥 CRÍTICA
-  **Impacto:** Alto (core do negócio)
-  **Status:** CRUD, PDF e dashboard implementados parcialmente

---

## 🔧 **BACKEND DEVELOPMENT**

### **📦 Models**

-  [x] Budget (app/Models/Budget.php)
   -  [x] Relacionamentos: customer, items, services
   -  [x] Campos: code, total, status

-  [x] BudgetItem, BudgetVersion, BudgetShare

### **📂 Repository Pattern**

-  [x] BudgetRepository — filtros e paginação
   -  [x] findByCode(), getNotCompleted()
   -  [ ] versões e histórico

### **🔧 Service Layer**

-  [x] BudgetService (app/Services/Domain/BudgetService.php)
   -  [x] create(), updateByCode(), findByCode()
   -  [x] getBudgetsForProvider(), cálculo de totais

---

## 🎮 **CONTROLLER & ROTAS**

### **🎯 BudgetController (app/Http/Controllers/BudgetController.php)**

-  [x] index(), create(), store()
-  [x] show(code), edit(code), update(code)
-  [x] print() — PDF (inline/download)
-  [x] dashboard() — métricas

### **🛣️ Rotas (routes/web.php)**

-  [x] Grupo `provider.budgets.*`
-  [x] Rotas públicas de compartilhamento com token

---

## 🎨 **FRONTEND INTERFACE**

### **📁 Views (resources/views/pages/budget/)**

-  [x] index, create, edit, show
-  [x] pdf_budget, pdf_budget_print
-  [x] dashboard

---

## 🧪 **TESTING**

-  [ ] Factories/Seeders
-  [ ] Unit: BudgetService
-  [ ] Feature: BudgetController
-  [ ] PDF e tokens públicos

---

## ✅ **VALIDAÇÃO FINAL**

-  [ ] CRUD completo e PDF
-  [ ] Dashboard funcional
-  [ ] Tokens públicos e segurança

---

## 🚨 **CHECKLIST DE DEPLOY**

-  [ ] Migrations e seeders
-  [ ] Cache/config otimizados
-  [ ] Testes passando

---

## 📊 **MÉTRICAS DE SUCESSO**

-  [ ] Tempo de resposta <3s (PDF)
-  [ ] Zero erros críticos
-  [ ] Integração com Services
