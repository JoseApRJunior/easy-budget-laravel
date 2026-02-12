
     * Exemplos de uso:
     *
     * ┌── Orçamento (por ID ou Código) - [!] Limpa histórico e sincroniza status dos serviços
     * │   php artisan dev:update-status budget 1 approved
     * │   php artisan dev:update-status budget ORC-2025-12-000001 draft
     * │
     * ├── Serviço
     * │   php artisan dev:update-status service SERV-2026-01-000001 on_hold
     * │
     * ├── Agendamento (código do serviço ou ID do agendamento)
     * │   php artisan dev:update-status schedule SERV-2026-01-000001 confirmed
     * │   php artisan dev:update-status schedule 1 finished
     * │
     * └── Serviço + Agendamento (simultâneo)
     *     php artisan dev:update-status service SERV-2026-01-000001 scheduling --sch=cancelled
     *


que acha desta minha ideia para implementar amanhã um Development (Dev) Onde você escreve o código e testa funcionalidades novas de forma bruta. Sua máquina local (Docker/XAMPP).
Staging (Homologação) Uma cópia idêntica à produção. Serve para o teste final antes do "deploy". Um VPS separado (mesmo que pequeno) ou uma partição isolada.
Production (Prod) Onde os usuários reais acessam. Deve ser o mais estável possível.

## Resumo do que falta fazer (Feature Flags & RBAC)

### 1. Reforço de RBAC nos Componentes de UI
- [ ] **Clientes**:
    - Adicionar `feature="customers"` ao botão "Cancelar" (voltar) em `resources/views/pages/customer/create.blade.php`.
    - Revisar `resources/views/pages/customer/edit.blade.php` e aplicar flags em todos os botões de ação.
- [ ] **Produtos**:
    - Finalizar a verificação em `resources/views/pages/product/index.blade.php`.
    - Validar botões dentro de modais de criação/edição de produtos.
- [ ] **Outros Módulos (Fora da pasta admin)**:
    - **Serviços**: Revisar listagens e botões de ação rápida.
    - **Faturas (Invoices)**: Garantir flags nos botões de download e envio.
    - **Relatórios**: Validar acesso aos botões de geração de PDF/Excel.

### 2. Validação e Consistência
- [ ] **Scan Global**: Executar uma busca por `<x-ui.button` em todos os arquivos fora de `resources/views/admin/` para identificar qualquer botão sem o atributo `feature`.
- [ ] **Alinhamento de Slugs**: Confirmar se todos os slugs usados (`customers`, `plans`, `manage-activities`, `manage-units`, `budgets`) estão corretamente definidos e ativos em `config/features.php`.

### 3. Infraestrutura e Ferramentas (Próximos Passos)
- [ ] **Configurações de Ambiente**: Revisar `mailtrap`, `queues` e `logs` para garantir que o fluxo de desenvolvimento -> homologação -> produção esteja isolado e funcional.
- [ ] **Scripts de Dev**: Integrar o comando `php artisan dev:update-status` ao fluxo de testes rápidos.
