# Plano de Migração Gradual do Sistema Legado para Laravel Blade, TailwindCSS e Vite

## Introdução
Este documento descreve o processo de migração gradual dos arquivos do sistema legado (localizado em `old-system/` e `resources/views-old/`) para a nova estrutura usando Laravel Blade para views, TailwindCSS para estilos e Vite para build de assets. A migração será realizada de forma incremental por módulos/funcionalidades, mantendo 100% da funcionalidade em cada etapa. Seguiremos as melhores práticas do Laravel, priorizando arquivos urgentes e documentando cada etapa.

## Identificação e Priorização de Arquivos
Baseado na estrutura do diretório `old-system/` e `resources/views-old/`, identificamos os módulos principais. A priorização é baseada na urgência (funcionalidades core como autenticação, dashboard e gerenciamento de orçamentos) e dependências.

### Prioridades Altas (Migrar Primeiro):
1. **Autenticação e Login**:
   - Views: `pages/login/index.twig`, `pages/user/confirm-account.twig`, etc.
   - Controllers: `controllers/LoginController.php`
   - Motivo: Funcionalidade essencial para acesso ao sistema.

2. **Home e Dashboard**:
   - Views: `pages/home/index.twig`, `pages/admin/dashboard.twig`, `pages/admin/home.twig`
   - Controllers: `controllers/HomeController.php`, `controllers/admin/DashboardController.php`
   - Motivo: Ponto de entrada principal para usuários.

3. **Gerenciamento de Orçamentos (Budget)**:
   - Views: `pages/budget/create.twig`, `pages/budget/index.twig`, `pages/budget/show.twig`
   - Controllers: `controllers/BudgetController.php`
   - Models/Repositories: `database/models/Budget.php`, `database/repositories/BudgetRepository.php`
   - Motivo: Funcionalidade central do aplicativo EasyBudget.

### Prioridades Médias:
4. **Faturas (Invoice)**:
   - Views: `pages/invoice/create.twig`, `pages/invoice/show.twig`
   - Controllers: `controllers/InvoiceController.php`

5. **Clientes (Customer)**:
   - Views: `pages/customer/create.twig`, `pages/customer/index.twig`
   - Controllers: `controllers/CustomerController.php`

### Prioridades Baixas:
6. **Relatórios e Admin Avançado**:
   - Views: `pages/report/index.twig`, `pages/admin/metrics-dashboard.twig`
   - Controllers: `controllers/ReportController.php`, `controllers/admin/MetricsDashboardController.php`

7. **Outros (Suporte, Planos, etc.)**:
   - Views e controllers relacionados a support, plans, etc.

## Etapas do Processo de Migração
A migração será incremental, focando em um módulo por vez. Para cada módulo:
- Testar funcionalidade antes e após migração.
- Usar Phpocalypse para análise de qualidade e testes.
- Documentar mudanças em seções subsequentes deste documento.

### 1. Migração de Visualizações
- Converter templates .twig de `resources/views-old/` para Blade em `resources/views/`.
- Manter estrutura: e.g., `pages/budget/create.twig` -> `resources/views/budget/create.blade.php`.
- Adaptar sintaxe Twig para Blade (e.g., {% for %} -> @foreach).

### 2. Adaptação de Estilos
- Converter CSS/SASS em `resources/assets-old/` para TailwindCSS.
- Identificar classes existentes e mapear para utilitários Tailwind.
- Testar visualmente para compatibilidade.

### 3. Configuração do Vite
- Atualizar `vite.config.js` para incluir entradas de JS/CSS.
- Configurar build para output em `public/build/`.
- Integrar Tailwind no pipeline.

### 4. Transferência de Assets
- Mover arquivos estáticos (imagens, etc.) para `public/`.
- Organizar em subpastas como `public/images/`, `public/js/`.

### 5. Testes e Validação
- Executar testes unitários e de integração após cada módulo.
- Verificar funcionalidade no navegador usando `open_preview`.

## Registro de Etapas Realizadas
(Seções serão adicionadas à medida que a migração avança)

### Etapa 1: Migração de Autenticação (Data: [Inserir Data])
- Arquivos migrados: [Listar]
- Mudanças: [Descrever]
- Testes: [Resultados]

... (Adicionar mais seções conforme necessário)

## Considerações Finais
- Manter backups do sistema legado.
- Usar versionamento Git para cada etapa.
- Em caso de problemas, usar modo Debug para troubleshooting.