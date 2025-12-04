# Easy Budget Laravel - Visão Geral do Produto

## Identidade do Projeto

**Nome:** Easy Budget Laravel  
**Tipo:** Sistema de Gestão Empresarial  
**Mercado-Alvo:** Prestadores de serviços, pequenas e médias empresas  
**Licença:** MIT

## Proposta de Valor

Easy Budget Laravel é um sistema completo de gestão empresarial que oferece controle total sobre relacionamento com clientes, operações financeiras, estoque e inteligência de negócios. Construído em Laravel 12 com padrões arquiteturais modernos, entrega recursos de nível empresarial com isolamento multi-tenant para operações seguras e escaláveis.

## Funcionalidades Principais

### 🏢 Arquitetura Multi-Tenant
- Isolamento completo de dados por empresa/tenant
- Consultas e operações com escopo de tenant seguro
- Arquitetura escalável suportando múltiplas organizações
- Autenticação e autorização com consciência de tenant

### 👥 CRM (Gestão de Relacionamento com Clientes)
- Tipos duplos de clientes: Pessoa Física (CPF) e Jurídica (CNPJ)
- Perfis completos de clientes com informações de contato
- Rastreamento e histórico de interações com clientes
- Marcação e categorização de clientes
- Busca de clientes baseada em geolocalização (CEP)
- Gestão de status de clientes (ativo/inativo)
- Exclusão suave com capacidade de restauração

### 💰 Gestão Financeira
- **Orçamentos/Cotações:** Criar, gerenciar e rastrear propostas de orçamento
- **Faturas:** Gerar faturas a partir de orçamentos ou independentes
- **Integração de Pagamento:** Integração com Mercado Pago para pagamentos online
- **Relatórios Financeiros:** Análises e relatórios financeiros abrangentes
- **Rastreamento de Pagamentos:** Monitorar status e histórico de pagamentos
- **Versionamento de Orçamentos:** Rastrear mudanças em orçamentos
- **Templates de Orçamento:** Templates de orçamento reutilizáveis

### 📦 Gestão de Estoque e Produtos
- Gestão de catálogo de produtos e serviços
- Rastreamento de estoque com histórico de movimentações
- Monitoramento e alertas de níveis de estoque
- Gestão de unidades (kg, litros, peças, etc.)
- Organização baseada em categorias
- Rastreamento de preços e custos de produtos

### 📊 Inteligência de Negócios
- Dashboards executivos com KPIs em tempo real
- Análises e estatísticas de clientes
- Métricas de desempenho financeiro
- Relatórios e insights de estoque
- Geração de relatórios personalizados
- Serviços de visualização de gráficos

### 🔐 Autenticação e Segurança
- Verificação híbrida de e-mail (Laravel Sanctum + Customizado)
- Login social (Google, Facebook via Socialite)
- Controle de acesso baseado em funções (RBAC)
- Sistema de gestão de permissões
- Sistema de tokens únicos com expiração de 30 minutos
- Registro de auditoria abrangente
- Gestão e segurança de sessões

### 📧 Comunicação e Notificações
- Sistema de notificações por e-mail
- Geração de e-mails baseada em templates
- Notificações de status de orçamento
- Notificações de faturas
- Sistema de tickets de suporte
- Registro e rastreamento de e-mails

### 🎨 Experiência do Usuário
- Interface responsiva Bootstrap 5.3
- Pipeline de assets moderno com Vite
- Hot Module Replacement (HMR) para desenvolvimento
- Recursos interativos com AJAX
- Validação de formulários em tempo real
- Sistema de alertas e notificações
- Layouts otimizados para mobile

## Usuários-Alvo

### Usuários Primários
- **Prestadores de Serviços:** Empresas oferecendo serviços profissionais
- **Pequenas Empresas:** Negócios de varejo, atacado e serviços
- **Médias Empresas:** Empresas em crescimento precisando de soluções escaláveis
- **Freelancers:** Profissionais individuais gerenciando múltiplos clientes

### Funções de Usuário
- **Administradores:** Acesso completo ao sistema e configuração
- **Gerentes:** Operações de negócios e relatórios
- **Equipe:** Operações do dia-a-dia e gestão de clientes
- **Clientes:** Acesso limitado a faturas e pagamentos (área pública)

## Casos de Uso Principais

### Fluxo de Gestão de Orçamentos
1. Criar perfil de cliente (pessoa física ou jurídica)
2. Gerar orçamento/cotação com itens de linha
3. Compartilhar orçamento com cliente
4. Rastrear status do orçamento (pendente, aprovado, rejeitado)
5. Converter orçamento aprovado em fatura
6. Processar pagamento via Mercado Pago
7. Gerar relatórios financeiros

### Fluxo de Gestão de Estoque
1. Adicionar produtos/serviços ao catálogo
2. Definir níveis iniciais de estoque
3. Rastrear movimentações de estoque (entrada/saída)
4. Monitorar alertas de estoque
5. Gerar relatórios de estoque
6. Atualizar preços e custos

### Fluxo de Relacionamento com Clientes
1. Registrar novo cliente (CPF/CNPJ)
2. Registrar interações com clientes
3. Rastrear histórico de clientes
4. Gerenciar status de clientes
5. Buscar clientes por localização
6. Gerar análises de clientes

### Fluxo de Relatórios Financeiros
1. Acessar dashboard executivo
2. Visualizar KPIs financeiros em tempo real
3. Gerar relatórios personalizados
4. Exportar dados para análise
5. Monitorar status de pagamentos
6. Rastrear tendências de receita

## Vantagens Competitivas

- **Stack Tecnológico Moderno:** Laravel 12, PHP 8.2+, Vite, Bootstrap 5.3
- **Pronto para Multi-Tenant:** Isolamento integrado para múltiplas organizações
- **Conjunto Abrangente de Recursos:** CRM + Finanças + Estoque em uma plataforma
- **Arquitetura Extensível:** Separação clara de responsabilidades com camada de serviço
- **Integração de Pagamento:** Integração pronta para uso com Mercado Pago
- **Amigável para Desenvolvedores:** Padrões e diretrizes de design bem documentados
- **Código Aberto:** Licença MIT permite customização e extensão
- **Desenvolvimento Ativo:** Atualizações e melhorias regulares

## Destaques Técnicos

- **PHP 8.2+** com recursos modernos da linguagem
- **Laravel 12** framework com recursos mais recentes
- **Vite** para bundling de assets rápido e moderno
- **MySQL 8.0+** com InnoDB para confiabilidade
- **Redis 7.0+** para cache e sessões
- **Multi-tenant** via pacote stancl/tenancy
- **Arquitetura orientada a eventos** para escalabilidade
- **Padrão repository** para abstração de dados
- **Camada de serviço** para lógica de negócio
- **Testes abrangentes** com PHPUnit e Dusk
