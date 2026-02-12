
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
