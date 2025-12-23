# 📋 **CHECKLIST SERVICES - MÓDULO INDIVIDUAL**

[⬅️ Voltar ao Índice](../INDICE_CHECKLISTS.md)

## 🎯 **Informações do Módulo:**

-  **Nome:** Services (Serviços)
-  **Dependências:** Budgets, Categories, Products, ServiceItems
-  **Prioridade:** 🟥 CRÍTICA
-  **Impacto:** Alto
-  **Status:** CRUD parcialmente implementado; status público por token

---

## 🔧 **BACKEND DEVELOPMENT**

### **📦 Models**

-  [x] Service (app/Models/Service.php)
   -  [x] Relacionamentos: budget, category, items
   -  [x] Campos: code, status, totals

-  [x] ServiceItem
   -  [x] Campos: descrição, quantidade, preço

### **📂 Repository Pattern**

-  [x] ServiceRepository — filtros por status/categoria/período
   -  [x] findByCode(), getFiltered()

### **🔧 Service Layer**

-  [x] ServiceService (app/Services/Domain/ServiceService.php)
   -  [x] create(), update(), changeStatus(), cancel()
   -  [x] getFilteredServices()

---

## 🎮 **CONTROLLER & ROTAS**

### **🎯 ServiceController (app/Http/Controllers/ServiceController.php)**

-  [x] dashboard() — métricas
-  [x] index(), create(), store()
-  [x] show(), edit(), update()
-  [x] change_status(), cancel(), destroy()
-  [x] viewServiceStatus(code, token) — público

### **🛣️ Rotas (routes/web.php)**

-  [x] Grupo `provider.services.*`
-  [x] Rotas de status público por token

---

## 🎨 **FRONTEND INTERFACE**

### **📁 Views (resources/views/pages/service/)**

-  [x] index, create, edit, show
-  [x] dashboard
-  [x] public/view-status

---

## 🧪 **TESTING**

-  [ ] Factories/Seeders
-  [ ] Unit: ServiceService
-  [ ] Feature: ServiceController
-  [ ] Público: view-status

---

## ✅ **VALIDAÇÃO FINAL**

-  [ ] CRUD + itens
-  [ ] Integração com Budget/Category/Product
-  [ ] Status público seguro

---

## 🚨 **CHECKLIST DE DEPLOY**

-  [ ] Migrations e seeders
-  [ ] Cache/config otimizados
-  [ ] Testes passando

---

## 📊 **MÉTRICAS DE SUCESSO**

-  [ ] Tempo de resposta <2s
-  [ ] Zero erros críticos
-  [ ] Baixo número de N+1 queries
