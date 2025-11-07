# 🚀 Prompts para Migração Completa: Budget Controller Legado → Laravel

## 📋 CONTEXTO DA MIGRAÇÃO

**Sistema:** Easy Budget Laravel  
**Componente:** BudgetController  
**Status Atual:** 25% implementado (3/12 métodos)  
**Objetivo:** Migração completa com paridade funcional  

---

## 🎯 PROMPT 1: IMPLEMENTAR CONTROLLERS CRÍTICOS AUSENTES

```
Implemente os métodos críticos ausentes no BudgetController baseado na análise:

MÉTODOS PARA IMPLEMENTAR:
1. store() - Criar orçamento com código único
2. show($code) - Visualizar orçamento completo  
3. update($code) - Formulário de edição
4. update_store() - Salvar edições
5. change_status() - Mudança de status em cascata
6. delete_store($code) - Soft delete com validações

REQUISITOS:
- Use BudgetService para lógica de negócio
- Implemente validações robustas
- Mantenha padrão de códigos: 'ORC-' + data + sequencial
- Mudança de status deve afetar serviços relacionados
- Use DB::transaction para operações complexas
- Retorne ServiceResult para consistência

ARQUIVOS A MODIFICAR:
- app/Http/Controllers/BudgetController.php
- app/Services/Domain/BudgetService.php
- routes/web.php (se necessário)

Siga os padrões do projeto: strict types, PHPDoc, error handling.
```

---

## 🎯 PROMPT 2: IMPLEMENTAR LÓGICA DE NEGÓCIO COMPLEXA

```
Implemente a lógica de negócio complexa ausente no BudgetService:

MÉTODOS PARA IMPLEMENTAR:
1. handleStatusChange() - Mudança de status em cascata
2. generateUniqueCode() - Geração de código com lock
3. validateStatusTransition() - Validar transições permitidas
4. updateRelatedServices() - Atualizar serviços em cascata
5. createFromTemplate() - Criar orçamento de template

LÓGICA DE CASCATA:
- Quando orçamento aprovado → serviços ficam "em andamento"
- Quando orçamento rejeitado → serviços ficam "cancelados"  
- Quando orçamento cancelado → cancelar todos os serviços
- Gerar fatura automaticamente quando aprovado

GERAÇÃO DE CÓDIGO:
- Padrão: 'ORC-' + YYYYMMDD + sequencial (4 dígitos)
- Use DB::transaction com lock para evitar duplicatas
- Busque último código do dia para incrementar

VALIDAÇÕES:
- Verificar se orçamento tem serviços antes de aprovar
- Validar se cliente está ativo
- Verificar permissões de mudança de status

Implemente com error handling robusto e logging via Observers.
```

---

## 🎯 PROMPT 3: IMPLEMENTAR SISTEMA DE PDF E TOKENS

```
Implemente o sistema de geração de PDF e gestão de tokens públicos:

COMPONENTES PARA CRIAR:
1. BudgetPdfService - Geração de PDF completa
2. BudgetTokenService - Gestão de tokens públicos
3. Migration para campos ausentes

PDF SERVICE:
- Gere PDF usando mPDF ou similar
- Inclua dados completos: cliente, itens, valores, observações
- Crie hash de verificação para integridade
- Salve PDF em storage/app/budgets/
- Retorne response com Content-Type correto

TOKEN SERVICE:
- Gere tokens únicos para acesso público
- Defina expiração (padrão: 7 dias)
- Implemente regeneração automática quando expira
- Valide tokens com verificação de expiração
- Log de acessos via token

MIGRATION ADICIONAL:
```sql
ALTER TABLE budgets ADD COLUMN history LONGTEXT NULL;
ALTER TABLE budgets ADD COLUMN pdf_verification_hash VARCHAR(64) NULL;
ALTER TABLE budgets ADD COLUMN public_token VARCHAR(43) NULL;
ALTER TABLE budgets ADD COLUMN public_expires_at TIMESTAMP NULL;
```

INTEGRAÇÃO:
- Atualize chooseBudgetStatus() para regenerar token expirado
- Atualize print() para gerar PDF real
- Adicione middleware para validação de token público

Mantenha compatibilidade com sistema legado.
```

---

## 🎯 PROMPT 4: CORRIGIR E COMPLETAR VIEWS

```
Corrija e complete as views do Budget baseado na análise:

VIEWS PARA CORRIGIR:
1. budgets/create.blade.php - Adicionar lista de clientes
2. budgets/show.blade.php - Criar view completa
3. budgets/edit.blade.php - Criar formulário de edição
4. budgets/index.blade.php - Adicionar filtros avançados

CREATE VIEW:
- Liste clientes ativos do tenant
- Formulário com campos: cliente, descrição, data vencimento
- Seção para adicionar itens dinamicamente
- Validação JavaScript em tempo real
- Cálculo automático de totais

SHOW VIEW:
- Exiba dados completos do orçamento
- Liste todos os itens com valores
- Mostre histórico de mudanças de status
- Botões de ação baseados no status atual
- Link para download de PDF

EDIT VIEW:
- Formulário pré-preenchido
- Permita edição apenas se status permitir
- Validação de campos obrigatórios
- Confirmação antes de salvar alterações

INDEX VIEW:
- Filtros: cliente, status, período, valor
- Paginação otimizada
- Ações em lote (aprovar múltiplos, etc.)
- Export para Excel/PDF

Use componentes Blade existentes e padrões do projeto.
```

---

## 🎯 PROMPT 5: IMPLEMENTAR TESTES AUTOMATIZADOS

```
Crie testes automatizados completos para o BudgetController:

TESTES DE CONTROLLER:
1. BudgetControllerTest - Testes de integração
2. BudgetServiceTest - Testes unitários de service
3. BudgetObserverTest - Testes de auditoria

CENÁRIOS DE TESTE:

CONTROLLER TESTS:
- test_index_returns_paginated_budgets()
- test_create_shows_form_with_customers()
- test_store_creates_budget_with_unique_code()
- test_show_displays_budget_details()
- test_update_shows_edit_form()
- test_update_store_saves_changes()
- test_change_status_updates_cascade()
- test_delete_store_soft_deletes()
- test_print_generates_pdf()
- test_choose_budget_status_validates_token()

SERVICE TESTS:
- test_generate_unique_code_with_lock()
- test_handle_status_change_cascade()
- test_validate_status_transition()
- test_create_from_template()
- test_update_related_services()

OBSERVER TESTS:
- test_audit_log_created_on_status_change()
- test_metadata_includes_old_new_values()
- test_ip_and_user_agent_recorded()

SETUP:
- Use factories para Budget, Customer, BudgetItem
- Mock external services (PDF, Email)
- Test database transactions
- Validate tenant scoping

Execute: php artisan test --filter=Budget
```

---

## 🎯 PROMPT 6: OTIMIZAÇÕES E PERFORMANCE

```
Implemente otimizações de performance para o sistema de orçamentos:

OTIMIZAÇÕES DE QUERY:
1. Eager loading otimizado
2. Índices de performance
3. Cache estratégico
4. Paginação eficiente

EAGER LOADING:
```php
// Otimize queries com relacionamentos
$budgets = Budget::with([
    'customer:id,name,email',
    'items:id,budget_id,description,quantity,unit_price',
    'services:id,budget_id,name,status'
])->paginate(15);
```

ÍNDICES NECESSÁRIOS:
```sql
-- Performance indexes
CREATE INDEX idx_budgets_tenant_status ON budgets(tenant_id, status);
CREATE INDEX idx_budgets_customer_date ON budgets(customer_id, created_at);
CREATE INDEX idx_budgets_code ON budgets(code);
CREATE INDEX idx_budget_items_budget ON budget_items(budget_id);
```

CACHE ESTRATÉGICO:
- Cache códigos gerados por dia
- Cache estatísticas de dashboard
- Cache PDFs gerados (24h)
- Cache tokens válidos

PAGINAÇÃO:
- Use cursor pagination para grandes datasets
- Implemente filtros eficientes
- Otimize contagem de registros

MONITORAMENTO:
- Log queries lentas (>100ms)
- Monitor uso de cache
- Track performance de PDF generation

Implemente com Laravel Telescope para debugging.
```

---

## 🎯 PROMPT 7: INTEGRAÇÃO COM SISTEMA LEGADO

```
Garanta compatibilidade total com o sistema legado durante a migração:

PONTOS DE COMPATIBILIDADE:
1. Formato de códigos de orçamento
2. Estrutura de dados exportados
3. URLs públicas existentes
4. Formato de PDFs gerados

MIGRAÇÃO DE DADOS:
```php
// Command para migrar dados legados
php artisan make:command MigrateLegacyBudgets

// Mapeamento de campos:
legacy.budget_code → budgets.code
legacy.budget_statuses_id → budgets.status (enum)
legacy.customer_id → budgets.customer_id
legacy.due_date → budgets.due_date
legacy.history → budgets.history (JSON)
```

COMPATIBILIDADE DE URLS:
- Mantenha URLs públicas existentes funcionando
- Redirecione URLs antigas para novas
- Preserve tokens públicos existentes

VALIDAÇÃO DE MIGRAÇÃO:
- Compare totais antes/depois
- Valide integridade de relacionamentos
- Teste funcionalidades críticas
- Backup completo antes da migração

ROLLBACK PLAN:
- Mantenha sistema legado como fallback
- Implemente feature flags para rollback rápido
- Monitor erros pós-migração
- Plano de comunicação com usuários

Execute migração em ambiente de staging primeiro.
```

---

## 🎯 PROMPT 8: DOCUMENTAÇÃO E DEPLOY

```
Crie documentação completa e prepare deploy da migração:

DOCUMENTAÇÃO TÉCNICA:
1. README da migração
2. Guia de troubleshooting
3. Changelog detalhado
4. Manual de operação

CONTEÚDO DA DOCUMENTAÇÃO:
- Diferenças entre legado e novo sistema
- Guia de migração passo-a-passo
- Troubleshooting de problemas comuns
- Performance benchmarks
- Backup e recovery procedures

DEPLOY CHECKLIST:
- [ ] Testes automatizados passando (100%)
- [ ] Performance tests validados
- [ ] Backup completo do sistema legado
- [ ] Migration scripts testados
- [ ] Rollback plan documentado
- [ ] Monitoring configurado
- [ ] Feature flags implementadas
- [ ] Comunicação com usuários preparada

MONITORAMENTO PÓS-DEPLOY:
- Error rates por endpoint
- Performance de queries críticas
- Uso de cache e Redis
- Geração de PDFs
- Logs de auditoria

MÉTRICAS DE SUCESSO:
- 0 erros críticos nas primeiras 24h
- Performance igual ou melhor que legado
- 100% dos orçamentos migrados corretamente
- Feedback positivo dos usuários

Prepare rollback automático se métricas não forem atingidas.
```

---

## 📊 RESUMO DOS PROMPTS

| Prompt | Foco | Prioridade | Tempo Estimado |
|--------|------|------------|----------------|
| 1 | Controllers Críticos | 🔴 Máxima | 2-3 dias |
| 2 | Lógica de Negócio | 🔴 Máxima | 2-3 dias |
| 3 | PDF e Tokens | 🟨 Alta | 1-2 dias |
| 4 | Views e Frontend | 🟨 Alta | 1-2 dias |
| 5 | Testes Automatizados | 🟩 Média | 1-2 dias |
| 6 | Performance | 🟩 Média | 1 dia |
| 7 | Compatibilidade | 🟨 Alta | 1 dia |
| 8 | Deploy e Docs | 🟩 Média | 1 dia |

**TOTAL ESTIMADO: 10-16 dias de desenvolvimento**

---

## 🚀 ORDEM DE EXECUÇÃO RECOMENDADA

### **Fase 1: Core (Crítica)**
1. Prompt 1: Controllers Críticos
2. Prompt 2: Lógica de Negócio

### **Fase 2: Features (Alta)**
3. Prompt 3: PDF e Tokens
4. Prompt 4: Views e Frontend

### **Fase 3: Qualidade (Média)**
5. Prompt 5: Testes Automatizados
6. Prompt 6: Performance

### **Fase 4: Deploy (Final)**
7. Prompt 7: Compatibilidade
8. Prompt 8: Deploy e Docs

---

## ✅ CRITÉRIOS DE SUCESSO

- [ ] Todos os 12 métodos do controller implementados
- [ ] Paridade funcional 100% com sistema legado
- [ ] Testes automatizados com cobertura >90%
- [ ] Performance igual ou superior ao legado
- [ ] Zero breaking changes para usuários finais
- [ ] Documentação completa e atualizada
- [ ] Deploy sem downtime
- [ ] Rollback plan testado e funcional

**Meta:** Sistema de orçamentos completamente migrado e operacional em produção.