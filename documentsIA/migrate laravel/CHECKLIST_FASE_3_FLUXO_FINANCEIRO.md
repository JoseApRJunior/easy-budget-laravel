# 📋 **CHECKLIST FASE 3 - FLUXO FINANCEIRO (Semanas 6-8)**

[⬅️ Voltar ao Índice](./INDICE_CHECKLISTS.md)

## 🎯 **Objetivo:** Implementar sistema completo de faturamento e pagamentos

### **Status Geral da Fase:**

-  **Prazo:** Semanas 6-8
-  **Prioridade:** CRÍTICA
-  **Impacto:** 🟥 CRÍTICO - Monetização e fechamento do ciclo de vendas
-  **Pré-requisitos:** FASE 2 100% concluída

---

## 💰 **6. INVOICES (FATURAS) - PRIORIDADE CRÍTICA**

### **📊 Informações do Módulo:**

-  **Status Atual:** Estrutura básica existe
-  **Dependências:** Budgets + Services + Customers (todos obrigatórios)
-  **Impacto:** 🟥 CRÍTICO - Faturamento e cobrança
-  **Tempo Estimado:** 8 dias

### **✅ Checklist de Desenvolvimento:**

#### **🔧 Backend (Models, Repositories, Services)**

-  [ ] Verificar e atualizar Invoice Model
   -  [ ] Relationships corretas (budget, customer, items)
   -  [ ] Fillable/casts adequados
   -  [ ] Traits TenantScoped e Auditable
   -  [ ] Status workflow (draft, sent, paid, overdue, cancelled)
   -  [ ] Cálculos automáticos de totais

-  [ ] Implementar InvoiceRepository completo
   -  [ ] Interface definida
   -  [ ] CRUD completo
   -  [ ] Busca por customer/status/datas
   -  [ ] Filtros avançados
   -  [ ] Relatórios financeiros

-  [ ] Implementar InvoiceService
   -  [ ] ServiceResult padronizado
   -  [ ] Geração automática a partir de orçamentos
   -  [ ] Cálculos de juros e multas
   -  [ ] Geração de códigos únicos
   -  [ ] Workflow de cobrança

#### **🎮 Controller - CRUD Completo**

-  [ ] **create()** - Criar fatura
   -  [ ] Formulário de criação manual
   -  [ ] Criação a partir de orçamento aprovado
   -  [ ] Seleção de itens/serviços
   -  [ ] Cálculo automático de totais

-  [ ] **store()** - Salvar fatura
   -  [ ] Validação de dados
   -  [ ] Gerar código único
   -  [ ] Criar InvoiceItems
   -  [ ] Calcular totais e impostos

-  [ ] **show()** - Visualizar fatura
   -  [ ] Detalhamento completo
   -  [ ] Histórico de pagamentos
   -  [ ] Status de cobrança
   -  [ ] Botões de ação (enviar, imprimir, cancelar)

-  [ ] **edit()** - Editar fatura
   -  [ ] Permitir edição apenas em status draft
   -  [ ] Atualizar itens
   -  [ ] Recalcular totais

-  [ ] **update()** - Atualizar fatura
   -  [ ] Validações por status
   -  [ ] Atualizar InvoiceItems
   -  [ ] Log de alterações

#### **📄 PDF Generation**

-  [ ] Implementar Invoice PDF profissional
   -  [ ] Layout similar ao orçamento
   -  [ ] Dados de cobrança
   -  [ ] QR Code para pagamento (PIX)
   -  [ ] Código de barras (boleto)
   -  [ ] Condições de pagamento

#### **💳 Payment Integration**

-  [ ] Integração Mercado Pago
   -  [ ] Geração de PIX
   -  [ ] Geração de boleto
   -  [ ] Cartão de crédito/débito
   -  [ ] Webhook para confirmação

-  [ ] Payment Tracking
   -  [ ] Registro de tentativas
   -  [ ] Status de pagamento
   -  [ ] Conciliação automática

#### **🎨 Interface (Views)**

-  [ ] Criar views em resources/views/pages/invoice/
   -  [ ] index.blade.php - listagem com filtros
   -  [ ] create.blade.php - formulário de criação
   -  [ ] show.blade.php - visualização detalhada
   -  [ ] edit.blade.php - formulário de edição
   -  [ ] dashboard.blade.php - métricas financeiras

---

## 💳 **7. PAYMENTS (PAGAMENTOS) - PRIORIDADE CRÍTICA**

### **📊 Informações do Módulo:**

-  **Status Atual:** Não implementado
-  **Dependências:** Invoices (obrigatório)
-  **Impacto:** 🟥 CRÍTICO - Recebimento de pagamentos
-  **Tempo Estimado:** 6 dias

### **✅ Checklist de Desenvolvimento:**

#### **🔧 Backend (Models, Repositories, Services)**

-  [ ] Criar Payment Model
   -  [ ] Relationships (invoice, customer)
   -  [ ] Status (pending, processing, completed, failed, refunded)
   -  [ ] Métodos de pagamento (pix, boleto, card, cash)
   -  [ ] Dados do gateway (transaction_id, gateway_response)

-  [ ] Implementar PaymentRepository
   -  [ ] CRUD completo
   -  [ ] Busca por invoice/customer/status
   -  [ ] Relatórios de recebimento

-  [ ] Implementar PaymentService
   -  [ ] Processamento de pagamentos
   -  [ ] Integração com gateways
   -  [ ] Conciliação automática
   -  [ ] Estornos e reembolsos

#### **🎮 Controller - Payment Processing**

-  [ ] **process()** - Processar pagamento
   -  [ ] Validação de dados
   -  [ ] Integração com gateway
   -  [ ] Atualização de status

-  [ ] **webhook()** - Receber confirmações
   -  [ ] Validação de assinatura
   -  [ ] Atualização automática de status
   -  [ ] Notificações ao cliente

-  [ ] **refund()** - Processar estornos
   -  [ ] Validações de segurança
   -  [ ] Integração com gateway
   -  [ ] Atualização de registros

#### **💰 Gateway Integration**

-  [ ] Mercado Pago Service
   -  [ ] Configuração de credenciais
   -  [ ] Geração de pagamentos
   -  [ ] Processamento de webhooks
   -  [ ] Tratamento de erros

---

## 📊 **8. FINANCIAL REPORTS (RELATÓRIOS FINANCEIROS) - PRIORIDADE ALTA**

### **📊 Informações do Módulo:**

-  **Status Atual:** Não implementado
-  **Dependências:** Invoices + Payments (obrigatórios)
-  **Impacto:** 🟨 ALTO - Gestão financeira e tomada de decisão
-  **Tempo Estimado:** 4 dias

### **✅ Checklist de Desenvolvimento:**

#### **📈 Dashboards Financeiros**

-  [ ] Dashboard de Receitas
   -  [ ] Receita mensal/anual
   -  [ ] Comparativo períodos
   -  [ ] Gráficos de tendência
   -  [ ] Top clientes

-  [ ] Dashboard de Cobrança
   -  [ ] Faturas em aberto
   -  [ ] Faturas vencidas
   -  [ ] Taxa de inadimplência
   -  [ ] Previsão de recebimento

#### **📋 Relatórios Detalhados**

-  [ ] Relatório de Vendas
   -  [ ] Por período
   -  [ ] Por cliente
   -  [ ] Por serviço/produto
   -  [ ] Margem de lucro

-  [ ] Relatório de Recebimentos
   -  [ ] Por método de pagamento
   -  [ ] Tempo médio de recebimento
   -  [ ] Taxa de conversão

-  [ ] Relatório de Inadimplência
   -  [ ] Clientes em atraso
   -  [ ] Valor total em aberto
   -  [ ] Histórico de pagamentos

#### **📤 Export e Integração**

-  [ ] Export para Excel/PDF
-  [ ] Agendamento de relatórios
-  [ ] Envio por email automático
-  [ ] API para integrações externas

---

## ✅ **CRITÉRIOS DE CONCLUSÃO DA FASE 3**

### **🎯 Validação Técnica:**

-  [ ] Invoices: CRUD completo + PDF + Status workflow
-  [ ] Payments: Processamento + Gateways + Webhooks
-  [ ] Reports: Dashboards + Relatórios + Export
-  [ ] Integração Mercado Pago funcionando
-  [ ] Conciliação automática operacional

### **🎯 Validação de Negócio:**

-  [ ] Usuário pode gerar faturas de orçamentos
-  [ ] Cliente pode pagar via PIX/Boleto/Cartão
-  [ ] Sistema atualiza status automaticamente
-  [ ] Relatórios financeiros precisos
-  [ ] Fluxo de cobrança completo

### **🎯 Valor para o Usuário:**

-  [ ] Sistema de faturamento 100% funcional
-  [ ] Recebimento automatizado
-  [ ] Controle financeiro completo
-  [ ] Relatórios gerenciais
-  [ ] Integração com meios de pagamento

---

## 🚨 **ALERTAS E RISCOS**

### **⚠️ Dependências Críticas:**

-  **Invoices** depende 100% de Budgets + Services da Fase 2
-  **Payments** depende 100% de Invoices
-  **Reports** depende de Invoices + Payments

### **🔍 Pontos de Atenção:**

-  Integração com Mercado Pago pode ser complexa
-  Webhooks precisam ser testados em produção
-  Conciliação automática é crítica
-  Relatórios devem ser performáticos

### **📞 Escalação:**

Se qualquer integração de pagamento falhar, **PARAR** e resolver antes de continuar.

---

**✅ Fase Anterior:** [CHECKLIST_FASE_2_CORE_BUSINESS.md](./CHECKLIST_FASE_2_CORE_BUSINESS.md)
**✅ Próxima Fase:** [CHECKLIST_FASE_4_ADVANCED_FEATURES.md](./CHECKLIST_FASE_4_ADVANCED_FEATURES.md)