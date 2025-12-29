# Relatório de Análise Técnica: Sistema Legado (Budget & Service)

**Data:** 26/12/2025
**Local:** `C:\laragon\www\easy-budget-laravel\old-system-legado`
**Escopo:** Análise detalhada dos módulos de Orçamentos (Budget) e Serviços (Service).

---

## 1. Cronologia e Fluxo de Trabalho

O sistema opera sob um fluxo linear dependente de aprovação externa (cliente) para avançar da fase de orçamento para a execução de serviços.

### Fluxo Principal
1.  **Criação (Provider):** O prestador cria um Orçamento (`Budget`).
    *   Estado inicial: `DRAFT` (Rascunho).
    *   O prestador adiciona Serviços (`Services`) ao orçamento.
    *   Cada serviço contém Itens (`ServiceItems` vinculados a `Products`).
2.  **Envio (Provider -> Customer):** O prestador altera o status para `PENDING`.
    *   O sistema gera um `UserConfirmationToken`.
    *   Um e-mail é enviado ao cliente com um link para aprovação.
    *   Os serviços associados mudam automaticamente de `DRAFT` para `PENDING`.
3.  **Aprovação (Customer):** O cliente acessa o link e aprova o orçamento.
    *   O orçamento muda para `APPROVED`.
    *   Os serviços mudam para `SCHEDULING` (Agendamento).
4.  **Agendamento e Execução (Provider):**
    *   O prestador agenda o serviço (status `SCHEDULED`), o que gera novo token e notificação para o cliente.
    *   O serviço progride para `PREPARING` -> `IN_PROGRESS`.
5.  **Conclusão:**
    *   O serviço é marcado como `COMPLETED` (ou `PARTIAL`, `CANCELLED`).
    *   O orçamento só pode ser marcado como `COMPLETED` se todos os serviços estiverem finalizados.

---

## 2. Ciclo de Vida e Dependências

### Ciclo de Vida do Budget (`BudgetStatusEnum`)
| Status | Descrição | Transições Permitidas |
| :--- | :--- | :--- |
| **DRAFT** | Criação/Edição. Único status editável. | PENDING, CANCELLED |
| **PENDING** | Aguardando cliente. Bloqueia edição. | APPROVED, REJECTED, EXPIRED, CANCELLED |
| **APPROVED** | Aprovado pelo cliente. | IN_PROGRESS, CANCELLED |
| **IN_PROGRESS** | Serviços em andamento. | COMPLETED, CANCELLED |
| **COMPLETED** | Finalizado com sucesso. | (Final) |
| **REJECTED** | Rejeitado pelo cliente. | (Final) |
| **CANCELLED** | Cancelado manualmente. | (Final) |

### Ciclo de Vida do Service (`ServiceStatusEnum`)
| Status | Gatilho de Entrada | Ações do Sistema |
| :--- | :--- | :--- |
| **DRAFT** | Criação do serviço. | Nenhuma. |
| **PENDING** | Orçamento enviado (`PENDING`). | Aguarda aprovação do orçamento. |
| **SCHEDULING** | Orçamento aprovado (`APPROVED`). | Habilita agendamento. |
| **SCHEDULED** | Agendamento definido. | Cria registro em `schedules`, gera Token, envia Email. |
| **PREPARING** | Preparação manual. | - |
| **IN_PROGRESS** | Início da execução. | Envia notificação. |
| **ON_HOLD** | Pausa manual. | Envia notificação. |
| **COMPLETED** | Conclusão manual. | Envia notificação. |

### Dependências Críticas
1.  **Hierarquia:** `Budget` (1) <-> (N) `Service` <-> (N) `ServiceItem`.
2.  **Sincronia de Status:** A alteração do status do Pai (`Budget`) força alterações em massa nos Filhos (`Services`). Ex: Rejeitar um orçamento reverte todos os serviços para `DRAFT`.
3.  **Tokens de Segurança:** A interação externa depende estritamente da tabela `user_confirmation_tokens`. Se o token expirar, o fluxo é bloqueado até a geração de um novo.

---

## 3. Regras de Negócio e Integrações

### Regras Principais
*   **Imutabilidade:** Orçamentos não podem ser editados se não estiverem em `DRAFT`.
*   **Totalização Automática:** O valor total do Orçamento é sempre a soma dos totais dos Serviços. O total do Serviço é a soma dos seus Itens.
*   **Bloqueio de Conclusão:** Um Orçamento não pode ser `COMPLETED` se houver serviços pendentes (não finalizados).
*   **Validação de Data:** Datas de vencimento e agendamento não podem ser retroativas.

### Integrações Identificadas
*   **NotificationService:** Disparo de e-mails transacionais (Aprovação, Agendamento, Status). O sistema é fortemente acoplado a este serviço; falhas no envio podem abortar transações de banco de dados.
*   **PdfService:** Geração de documentos PDF para orçamentos e ordens de serviço, protegidos por hash de verificação.
*   **Mercado Pago:** Integração detectada via tabelas (`merchant_orders_mercado_pago`, `payment_mercado_pago_*`) e Controllers, embora a lógica de serviço não tenha sido auditada neste escopo.

---

## 4. Inconsistências e Pontos de Atenção

### 1. Duplicação de Código Crítica
A lógica de criação de serviços está duplicada e idêntica em dois locais:
*   `app/database/services/BudgetService.php` (método `createService`)
*   `app/database/services/ServiceService.php` (método `createService`)
**Risco:** Alterações em um não refletem no outro, causando inconsistência de dados.

### 2. Acoplamento com Infraestrutura
Nos métodos de mudança de status (ex: `handleStatusChange`), o sistema retorna erro e *faz rollback* no banco de dados se o envio de e-mail falhar.
**Impacto:** Se o servidor de e-mail oscilar, o sistema torna-se inoperante para mudanças de status. A notificação deveria ser assíncrona ou não-bloqueante.

### 3. Hardcoded IDs
O código utiliza "Magic Numbers" para status em vez de constantes ou consultas ao banco.
*   Exemplo: `$properties['service_statuses_id'] = 1;` (Assumindo que 1 é Rascunho).
**Risco:** Se a tabela `service_statuses` for recriada com IDs diferentes, a lógica quebra silenciosamente.

### 4. Complexidade Ciclomática
O método `handleStatusChange` em `BudgetService` e `ServiceService` é excessivamente longo, baseado em `switch/case` gigantescos que misturam validação, persistência e notificação.

---

## 5. Recomendações para Próxima Fase

1.  **Refatoração Imediata:**
    *   Centralizar a criação de serviços em apenas uma classe (`ServiceService`).
    *   Substituir IDs numéricos (`1`) pelo uso dos Enums ou busca pelo `slug`.
2.  **Desacoplamento:**
    *   Isolar o envio de e-mails em Eventos/Filas (Jobs). O erro no envio de e-mail não deve impedir a atualização do banco de dados.
3.  **Arquitetura:**
    *   Implementar o **Padrão State** para gerenciar as transições de status, removendo os `switch/case` gigantes dos Services.
4.  **Banco de Dados:**
    *   Considerar mover a lógica de status dinâmico (tabelas `_statuses`) totalmente para Enums se a personalização pelo usuário não for um requisito, ou garantir que os IDs sejam fixos via Seeding robusto.

---
*Relatório gerado automaticamente pela Assistente de IA.*
