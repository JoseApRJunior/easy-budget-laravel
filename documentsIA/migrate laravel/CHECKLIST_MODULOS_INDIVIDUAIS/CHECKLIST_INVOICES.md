# 📋 **CHECKLIST INVOICES - MÓDULO INDIVIDUAL**

[⬅️ Voltar ao Índice](../INDICE_CHECKLISTS.md)

## 🎯 **Informações do Módulo:**

-  **Nome:** Invoices (Faturas)
-  **Dependências:** Customers, Services, Payments (MercadoPago)
-  **Prioridade:** 🟨 MÉDIA
-  **Impacto:** Financeiro
-  **Status:** CRUD e dashboard implementados parcialmente

---

## 🔧 **BACKEND DEVELOPMENT**

### **📦 Models**

-  [x] Invoice (app/Models/Invoice.php)
   -  [x] Relacionamentos: customer, service, invoiceItems, payments
   -  [x] Campos: code, status, total, transaction_amount

-  [x] InvoiceItem

### **📂 Repository Pattern**

-  [x] InvoiceRepository — filtros por status/cliente/período
   -  [x] findByCode(), getFiltered()

### **🔧 Service Layer**

-  [x] InvoiceService (app/Services/Domain/InvoiceService.php)
   -  [x] createInvoice(), getFilteredInvoices()
   -  [ ] integração com pagamentos

---

## 🎮 **CONTROLLER & ROTAS**

### **🎯 InvoiceController (app/Http/Controllers/InvoiceController.php)**

-  [x] index(), create(), store()
-  [x] show(code), edit(code), update(code)
-  [x] destroy(code)
-  [x] dashboard() — métricas

### **🛣️ Rotas (routes/web.php)**

-  [x] Grupo `provider.invoices.*`
-  [x] Rotas públicas para visualização e pagamento

---

## 🎨 **FRONTEND INTERFACE**

### **📁 Views (resources/views/pages/invoice/)**

-  [x] index, create, edit, show
-  [x] dashboard
-  [x] public/view-status, public/print

---

## 🧪 **TESTING**

-  [ ] Factories/Seeders
-  [ ] Unit: InvoiceService
-  [ ] Feature: InvoiceController
-  [ ] Integração de pagamentos

---

## ✅ **VALIDAÇÃO FINAL**

-  [ ] CRUD + itens
-  [ ] Dashboard funcional
-  [ ] Integração MercadoPago

---

## 🚨 **CHECKLIST DE DEPLOY**

-  [ ] Migrations e seeders
-  [ ] Cache/config otimizados
-  [ ] Testes passando

---

## 📊 **MÉTRICAS DE SUCESSO**

-  [ ] Tempo de resposta <2s
-  [ ] Zero erros críticos
-  [ ] Recebimentos e pendências consistentes
