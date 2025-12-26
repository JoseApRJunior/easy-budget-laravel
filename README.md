# Easy Budget - Plataforma de Orçamentos Inteligente

## 🎯 Nossa Missão

No **Easy Budget**, nossa missão é revolucionar a forma como prestadores de serviços e clientes se conectam. Buscamos simplificar e otimizar todo o processo de orçamentação, gestão e execução de serviços. Oferecemos ferramentas intuitivas e poderosas para que **Pessoas Físicas** e **Jurídicas** possam profissionalizar seus negócios, economizar tempo e aumentar sua lucratividade com transparência e eficiência.

---

## 💼 Recursos Principais para o Prestador

O Easy Budget oferece um ecossistema completo para a gestão do seu negócio:

*   **Gestão de Clientes (CRM):** Cadastro completo, histórico de serviços e preferências.
*   **Catálogo de Produtos e Serviços:** Cadastro flexível com categorias, unidades de medida e controle de estoque integrado.
*   **Orçamentos Profissionais:** Criação rápida de orçamentos detalhados, envio digital e aprovação online.
*   **Agendamento Inteligente:** Agenda integrada para organizar a execução dos serviços confirmados.
*   **Financeiro Completo:** Emissão de faturas, controle de pagamentos e integração direta com **Mercado Pago**.
*   **Relatórios de Desempenho:** Análise de lucratividade, serviços mais vendidos e faturamento mensal.
*   **Notificações Automáticas:** Alertas sobre novos agendamentos, pagamentos recebidos e vencimentos.
*   **Ferramentas de Compartilhamento:** Envio fácil de orçamentos via Link ou QR Code.
*   **Multitenancy:** Arquitetura de sistema que permite que múltiplas empresas (Tenants) usem a mesma plataforma de forma isolada e segura. Cada empresa tem seus próprios dados e clientes (sejam eles PF ou PJ), sem interferência entre contas.

---

## 🔄 Fluxo de Trabalho Detalhado

O sistema opera sob um fluxo linear e seguro, garantindo que cada etapa seja validada antes de avançar.

### Fluxo Principal (Provider & Customer)
1.  **Criação (Provider):** O prestador cria um Orçamento (`Budget`).
    *   **Estado inicial:** `DRAFT` (Rascunho).
    *   O prestador adiciona Serviços (`Services`) ao orçamento.
    *   Cada serviço contém Itens (`ServiceItems` vinculados a `Products`).
2.  **Envio (Provider -> Customer):** O prestador altera o status para `PENDING`.
    *   O sistema gera um `UserConfirmationToken` único e seguro.
    *   Um e-mail é enviado ao cliente com um link para visualização e aprovação.
    *   **Automação:** Os serviços associados mudam automaticamente de `DRAFT` para `PENDING`.
3.  **Aprovação (Customer):** O cliente acessa o link e aprova o orçamento.
    *   O orçamento muda para `APPROVED`.
    *   **Automação:** Os serviços mudam para `SCHEDULING` (Agendamento liberado).
4.  **Agendamento e Execução (Provider):**
    *   O prestador agenda o serviço (status `SCHEDULED`), o que gera um novo token de confirmação e notificação para o cliente.
    *   O serviço progride para `PREPARING` -> `IN_PROGRESS` (Em execução).
5.  **Conclusão:**
    *   O serviço é marcado como `COMPLETED` (ou `PARTIAL` se houver pendências, ou `CANCELLED`).
    *   **Regra de Ouro:** O orçamento só pode ser marcado como `COMPLETED` se **todos** os serviços estiverem finalizados.

---

## ⚙️ Ciclo de Vida e Regras de Negócio

### Ciclo de Vida do Orçamento (Budget)
| Status | Descrição | Transições Permitidas |
| :--- | :--- | :--- |
| **DRAFT** | Criação/Edição. Único status que permite alterações. | PENDING, CANCELLED |
| **PENDING** | Aguardando cliente. Bloqueia qualquer edição. | APPROVED, REJECTED, EXPIRED, CANCELLED |
| **APPROVED** | Aprovado pelo cliente. Habilita agendamento. | IN_PROGRESS, CANCELLED |
| **IN_PROGRESS** | Serviços estão sendo executados. | COMPLETED, CANCELLED |
| **COMPLETED** | Finalizado com sucesso. | (Estado Final) |
| **REJECTED** | Rejeitado pelo cliente. | (Estado Final) |
| **CANCELLED** | Cancelado manualmente pelo prestador. | (Estado Final) |

### Ciclo de Vida do Serviço (Service)
| Status | Gatilho de Entrada | Ações do Sistema |
| :--- | :--- | :--- |
| **DRAFT** | Criação do serviço. | Nenhuma ação externa. |
| **PENDING** | Orçamento enviado (`PENDING`). | Aguarda aprovação do orçamento. |
| **SCHEDULING** | Orçamento aprovado (`APPROVED`). | Habilita botão de agendamento. |
| **SCHEDULED** | Agendamento definido. | Cria registro na agenda, gera Token e envia E-mail. |
| **PREPARING** | Preparação manual. | Prepara insumos/estoque. |
| **IN_PROGRESS** | Início da execução. | Envia notificação de "Em andamento". |
| **ON_HOLD** | Pausa manual. | Envia notificação de "Pausa". |
| **COMPLETED** | Conclusão manual. | Envia notificação de "Concluído". |

### Regras de Negócio Críticas
1.  **Hierarquia Rígida:** `Budget` (Pai) -> `Service` (Filho) -> `ServiceItem` (Neto).
2.  **Sincronia de Status:** Alterar o status do Orçamento força a atualização de todos os Serviços.
    *   *Exemplo:* Se o cliente rejeita o orçamento, todos os serviços voltam para rascunho ou são cancelados.
3.  **Imutabilidade:** Orçamentos enviados (`PENDING`) são travados para edição. Para alterar, é necessário cancelar e criar um novo ou reverter para rascunho (se permitido).
4.  **Totalização Automática:** O valor do Orçamento é sempre a soma dos Serviços. O valor do Serviço é a soma dos Itens.
5.  **Validação de Datas:** O sistema impede agendamentos com datas retroativas.
6.  **Bloqueio de Conclusão:** É impossível finalizar um Orçamento se houver serviços pendentes.

---

## 🧠 Inteligência Artificial (Easy Budget AI)

Estamos integrando IA para transformar dados em decisões estratégicas para o prestador.

### 🤖 IA Generativa (Assistente Criativo)
*   **Criação Automática de Descrições:** A IA sugere descrições atraentes e detalhadas para orçamentos e serviços com base em poucas palavras-chave.
*   **Sugestão de Respostas:** Respostas rápidas e profissionais para dúvidas de clientes no chat integrado.

### 📊 IA Analítica (Insights de Negócio)
A IA analisa os dados do prestador para fornecer inteligência de mercado:
*   **Previsão de Demanda:** "Baseado no seu histórico, a procura por *Serviço X* tende a aumentar no próximo mês."
*   **Otimização de Preços:** Sugestões de ajuste de preços baseadas na margem de lucro e aceitação dos orçamentos.
*   **Análise de Clientes:** Identificação de clientes com maior potencial de compra (LTV) e risco de cancelamento (Churn).
*   **Insights de Inventário:** Alertas preditivos de ruptura de estoque antes que os produtos acabem.
*   **Mapa de Calor:** Identificação das regiões geográficas onde o prestador tem maior aceitação.

---

## 🏢 Administração Geral (Super Admin Multitenant)

O painel administrativo é focado na saúde da plataforma SaaS, garantindo escalabilidade e monitoramento sem violar a privacidade dos usuários.

### Funcionalidades do Super Admin
*   **Gestão de Planos e Assinaturas:** Criação dinâmica de planos (Free, Pro, Enterprise), definição de limites (número de orçamentos, usuários) e preços.
*   **Gestão de Tenants (Prestadores):** Visão geral dos usuários cadastrados, status da conta (ativo/inativo) e plano vigente.
*   **Dashboard de Métricas (SaaS):**
    *   **MRR (Receita Recorrente Mensal):** Acompanhamento financeiro da plataforma.
    *   **Churn Rate:** Taxa de cancelamento de assinaturas.
    *   **Novos Cadastros:** Monitoramento do crescimento da base de usuários.
*   **Monitoramento de Performance:** Identificação de gargalos no sistema, erros de integração e latência.

### 🔒 Privacidade e Segurança
*   **Acesso Restrito:** O Super Admin **NÃO** tem acesso aos dados sensíveis dos clientes finais dos prestadores (ex: orçamentos específicos, dados de clientes dos prestadores).
*   **Auditoria:** O acesso é estritamente focado em métricas de uso e suporte técnico, respeitando os termos de uso e LGPD.

---

## 🚀 Cronologia e Jornada do Usuário

### 1. Onboarding do Prestador
1.  **Registro:** Cadastro simplificado (E-mail ou Social Login).
2.  **Configuração Inicial:** Definição de perfil (PF/PJ), dados comerciais e preferências.
3.  **Escolha de Plano:** Seleção de plano com período de teste (Trial de 7 dias).
4.  **Integração Financeira:** Conexão segura com conta Mercado Pago para recebimentos.

### 2. Ciclo Operacional (Dia a Dia)
1.  **Organização:** Cadastro de Clientes, Produtos e Serviços.
2.  **Venda:** Criação e envio de Orçamento (`Budget`) para o cliente.
3.  **Aprovação:** Cliente aprova o orçamento online.
4.  **Agendamento:** Prestador define a data de execução (`Service Scheduling`).
5.  **Execução:** Realização do serviço e baixa no sistema.
6.  **Faturamento:** Geração de fatura e recebimento do pagamento.
7.  **Pós-Venda:** Envio de pesquisa de satisfação e relatórios.

---

## 📋 Detalhamento dos Recursos

### # Criação e Gestão de Orçamentos
Ferramenta poderosa onde o prestador monta propostas comerciais. Permite incluir múltiplos serviços, produtos, descontos e validade. O cliente recebe um link exclusivo onde pode **Aprovar**, **Recusar** ou solicitar **Alterações**.

### # Agendamentos e QR Code
Após a aprovação, o serviço entra na fila de agendamento. O sistema gera um **QR Code** único para cada visita técnica ou serviço, que pode ser escaneado pelo cliente para confirmar a presença e a execução do trabalho, garantindo segurança para ambos.

### # Gestão Financeira e Faturas
Controle total do fluxo de caixa. O sistema identifica pagamentos via Mercado Pago e dá baixa automática nas faturas. Permite visualização clara de **A Receber**, **Vencidos** e **Pagos**.

### # Estoque Inteligente
Controle de entrada e saída de insumos. O sistema desconta automaticamente do estoque os produtos utilizados em serviços realizados e alerta quando é hora de repor.

### # Notificações Multicanal
Sistema de alertas via E-mail e Push (futuro App) para manter prestador e cliente informados sobre cada etapa do processo (Orçamento visto, Orçamento aprovado, Pagamento confirmado, Lembrete de agendamento).

### # Integrações Essenciais
*   **NotificationService:** Motor robusto para envio de e-mails transacionais.
*   **PdfService:** Gerador de documentos com hash de autenticidade.
*   **Mercado Pago:** Gateway de pagamento nativo para assinaturas e recebíveis.
