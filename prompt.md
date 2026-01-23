
     * Exemplos de uso:
     *
     * ┌── Orçamento (por ID ou Código)
     * │   php artisan dev:update-status budget 1 approved
     * │   php artisan dev:update-status budget BUD-2026-01-000001 approved
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
