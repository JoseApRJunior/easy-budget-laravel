# 🏗️ Migração de Estrutura de Serviços

Este documento explica como usar o script automatizado para reorganizar a estrutura de serviços do Easy Budget Laravel seguindo os princípios de **Clean Architecture** e **Domain-Driven Design**.

## 📋 Visão Geral

O script automatiza a migração de **47 serviços** atuais para uma estrutura organizada por camadas:

```
app/Services/
├── 📂 Domain/           # 18 serviços - CRUD e regras de negócio
├── 📂 Application/      # 14 serviços - Coordenação complexa
├── 📂 Infrastructure/   # 16 serviços - APIs e integrações externas
├── 📂 Core/            # Abstrações e contratos
└── 📂 Shared/          # Serviços utilitários comuns
```

## 🚀 Como Usar

### Método 1: Comando Artisan (Recomendado)

```bash
# 1. Primeiro, execute em modo simulação para ver o plano
php artisan services:migrate-structure --dry-run

# 2. Se estiver satisfeito com o plano, execute com backup
php artisan services:migrate-structure --backup

# 3. Para forçar sobrescrita de arquivos existentes
php artisan services:migrate-structure --backup --force
```

### Método 2: Script Shell (Mais Simples)

```bash
# 1. Primeiro teste em modo simulação
./migrate-services.sh --dry-run

# 2. Execute a migração real
./migrate-services.sh --backup

# 3. Para ver todas as opções
./migrate-services.sh --help
```

## ⚙️ Opções Disponíveis

| Opção       | Descrição                            | Recomendação               |
| ----------- | ------------------------------------ | -------------------------- |
| `--dry-run` | Modo simulação - não altera arquivos | ✅ **Sempre use primeiro** |
| `--backup`  | Cria backup antes da migração        | ✅ **Recomendado**         |
| `--force`   | Força execução mesmo com conflitos   | ⚠️ **Use com cuidado**     |

## 📊 O que o Script Faz

### ✅ Funcionalidades Automáticas

1. **Análise Inteligente**: Categoriza serviços baseado em padrões e keywords
2. **Criação de Estrutura**: Cria pastas organizadas por camadas arquiteturais
3. **Migração Segura**: Move arquivos para locais apropriados
4. **Atualização de Namespaces**: Ajusta namespaces nos arquivos migrados
5. **Relatório Detalhado**: Gera relatório JSON com resultados da migração
6. **Backup Automático**: Cria cópia de segurança quando solicitado

### 🎯 Categorização Automática

#### **Domain Services (18 serviços)**

-  `CustomerService`, `ProductService`, `BudgetService`
-  `UserService`, `RoleService`, `CategoryService`
-  **Responsabilidade**: CRUD e regras de negócio específicas

#### **Application Services (14 serviços)**

-  `BudgetCalculationService`, `UserRegistrationService`
-  `BudgetPdfService`, `EmailTemplateService`
-  **Responsabilidade**: Coordenação de múltiplos Domain Services

#### **Infrastructure Services (16 serviços)**

-  `MercadoPagoService`, `PdfService`, `MailerService`
-  `CacheService`, `ChartService`, `PaymentService`
-  **Responsabilidade**: Integração com serviços externos

## 📋 Fluxo de Migração

### **Fase 1: Análise e Planejamento**

```bash
php artisan services:migrate-structure --dry-run
```

-  ✅ Analisa 47 serviços existentes
-  ✅ Categoriza automaticamente por responsabilidade
-  ✅ Exibe plano detalhado de migração
-  ✅ Identifica possíveis conflitos

### **Fase 2: Execução Segura**

```bash
php artisan services:migrate-structure --backup
```

-  ✅ Cria backup automático
-  ✅ Migra arquivos para novas pastas
-  ✅ Atualiza namespaces automaticamente
-  ✅ Gera relatório de execução

### **Fase 3: Validação**

-  ✅ Verificar se todos os serviços foram migrados
-  ✅ Testar funcionalidades críticas
-  ✅ Verificar namespaces atualizados
-  ✅ Revisar relatório gerado

## 🔧 Categorização Detalhada

### **Domain Services**

```php
// Regras de negócio puras e operações CRUD
ActivityService.php      → Domain
AddressService.php       → Domain
AuditService.php         → Domain
BudgetService.php        → Domain
CategoryService.php      → Domain
CommonDataService.php    → Domain
ContactService.php       → Domain
CustomerService.php      → Domain
InvoiceService.php       → Domain
PlanService.php          → Domain
ProductService.php       → Domain
ProviderService.php      → Domain
ReportService.php        → Domain
RoleService.php          → Domain
ServiceService.php       → Domain
SettingsService.php      → Domain
SupportService.php       → Domain
UserService.php          → Domain
```

### **Application Services**

```php
// Coordenação de workflows complexos
BudgetCalculationService.php    → Application
BudgetPdfService.php           → Application
BudgetStatusService.php        → Application
BudgetTemplateService.php      → Application
CustomerInteractionService.php → Application
EmailTemplateService.php       → Application
EmailTrackingService.php       → Application
ExportService.php              → Application
FileUploadService.php          → Application
InvoiceStatusService.php       → Application
ProviderManagementService.php  → Application
ServiceStatusService.php       → Application
SettingsBackupService.php      → Application
UserRegistrationService.php    → Application
```

### **Infrastructure Services**

```php
// Integrações com serviços externos
CacheService.php                    → Infrastructure
ChartService.php                    → Infrastructure
ChartVisualizationService.php       → Infrastructure
EncryptionService.php               → Infrastructure
FinancialSummary.php                → Infrastructure
GeolocationService.php              → Infrastructure
MailerService.php                   → Infrastructure
MercadoPagoService.php              → Infrastructure
MerchantOrderMercadoPagoService.php → Infrastructure
MetricsService.php                  → Infrastructure
NotificationService.php             → Infrastructure
PaymentMercadoPagoInvoiceService.php → Infrastructure
PaymentMercadoPagoPlanService.php   → Infrastructure
PaymentService.php                  → Infrastructure
PdfService.php                      → Infrastructure
VariableProcessor.php               → Infrastructure
WebhookService.php                  → Infrastructure
```

## 📋 Arquivos Gerados

### **Relatório de Migração**

```
storage/app/services-migration-report-YYYY-MM-DD-HH-II-SS.json
```

Contém:

-  Lista de serviços migrados
-  Erros encontrados
-  Estatísticas da migração
-  Timestamp da execução

### **Backup (quando solicitado)**

```
storage/app/services-migration-backup-YYYY-MM-DD-HH-II-SS/
```

-  Cópia completa da pasta `app/Services/` antes da migração

## ⚠️ Cuidados Importantes

### **Antes da Migração**

-  ✅ Faça backup manual do projeto
-  ✅ Teste em ambiente de desenvolvimento
-  ✅ Execute primeiro com `--dry-run`
-  ✅ Revise o plano de migração exibido

### **Durante a Migração**

-  ⏳ Não interrompa o processo
-  ⏳ Aguarde conclusão completa
-  ⏳ Verifique se não há processos usando os arquivos

### **Após a Migração**

-  ✅ Teste funcionalidades críticas
-  ✅ Verifique se namespaces estão corretos
-  ✅ Execute testes automatizados
-  ✅ Revise o relatório gerado

## 🔧 Resolução de Problemas

### **Erro: "Arquivo já existe no destino"**

```bash
# Use a opção --force para sobrescrever
php artisan services:migrate-structure --backup --force
```

### **Erro: "Namespace não encontrado"**

-  Verifique se todos os arquivos foram migrados corretamente
-  Certifique-se de que as pastas foram criadas
-  Revise o relatório de migração

### **Erro: "Permissão negada"**

```bash
# Ajuste permissões se necessário
chmod +x migrate-services.sh
```

## 📞 Suporte

Se encontrar problemas durante a migração:

1. **Verifique o relatório** gerado em `storage/app/`
2. **Revise os logs** do Laravel
3. **Restaure o backup** se necessário
4. **Execute novamente** com `--dry-run` para diagnóstico

## 🎯 Benefícios Alcançados

Após a migração bem-sucedida:

-  ✅ **Clareza arquitetural** - responsabilidades bem definidas
-  ✅ **Manutenibilidade** - localização rápida de serviços
-  ✅ **Escalabilidade** - adição fácil de novos serviços
-  ✅ **Testabilidade** - dependências claras entre camadas
-  ✅ **Documentação viva** - estrutura reflete arquitetura

---

**Última atualização:** 10/10/2025
**Versão do script:** 1.0.0
**Status:** ✅ Pronto para uso em produção
