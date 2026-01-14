Baseado na análise dos arquivos listados em `app/Models` e `app/DTOs`, identifiquei os seguintes modelos que ainda não possuem DTOs correspondentes.

**Lista de Models sem DTOs (Priorizados por relevância de negócio):**

1.  **Budget (Orçamentos)**
    *   `BudgetActionHistory`
    *   `BudgetAttachment`
    *   `BudgetCalculationSettings`
    *   `BudgetItemCategory`
    *   `BudgetNotification`

2.  **Customer (Clientes)**
    *   `CustomerTag`

3.  **Payment (Pagamentos)**
    *   `PaymentMercadoPagoInvoice`
    *   `PaymentMercadoPagoPlan`

4.  **Reports (Relatórios)**
    *   `ReportDefinition`
    *   `ReportExecution`
    *   `ReportSchedule`

5.  **Notifications & Logs (Notificações e Logs)**
    *   `EmailLog`
    *   `EmailTemplate`
    *   `EmailVariable`
    *   `Notification`
    *   `MonitoringAlertHistory`
    *   `MonitoringAlertsHistory`
    *   `MiddlewareMetricHistory`

6.  **Provider & User (Provedor e Usuário)**
    *   `ProviderCredential`
    *   `UserConfirmationToken`
    *   `UserRole`
    *   `UserSettings`

7.  **Outros/Auxiliares**
    *   `Pdf`
    *   `Resource`
    *   `Session`
    *   `WebhookRequest`
    *   `TestModel` (Ignorar)

**Plano de Ação:**

Vou implementar os DTOs para os grupos de maior prioridade primeiro (1, 2 e 3), pois impactam diretamente as regras de negócio principais (Orçamentos, Clientes e Pagamentos).

1.  **Budget DTOs:** Criar `BudgetActionHistoryDTO`, `BudgetAttachmentDTO`, `BudgetCalculationSettingsDTO`, `BudgetItemCategoryDTO`, `BudgetNotificationDTO`.
2.  **Customer DTOs:** Criar `CustomerTagDTO`.
3.  **Payment DTOs:** Criar `PaymentMercadoPagoInvoiceDTO`, `PaymentMercadoPagoPlanDTO`.
4.  **Report DTOs:** Criar `ReportDefinitionDTO`, `ReportExecutionDTO`, `ReportScheduleDTO`.
5.  **User/Provider DTOs:** Criar `ProviderCredentialDTO`, `UserSettingsDTO`.

Os DTOs de logs e histórico serão deixados para uma etapa posterior, pois geralmente são preenchidos internamente pelo sistema e não via input de usuário.