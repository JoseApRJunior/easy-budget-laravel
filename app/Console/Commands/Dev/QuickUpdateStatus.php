<?php

declare(strict_types=1);

namespace App\Console\Commands\Dev;

use App\Models\Budget;
use App\Models\Service;
use App\Models\Schedule;
use Illuminate\Console\Command;

class QuickUpdateStatus extends Command
{

    /**
     * Exemplos de uso:
     *
     * 1. Atualizar Orçamento por ID ou Código:
     * php artisan dev:update-status budget 1 approved
     * php artisan dev:update-status budget BUD-2026-01-000001 approved
     *
     * 2. Atualizar Serviço:
     * php artisan dev:update-status service SERV-2026-01-000001 on_hold
     *
     * 3. Atualizar Agendamento (usando código do serviço ou ID do agendamento):
     * php artisan dev:update-status schedule SERV-2026-01-000001 confirmed
     * php artisan dev:update-status schedule 1 finished
     *
     * 4. Atualizar Serviço e Agendamento vinculado simultaneamente:
     * php artisan dev:update-status service SERV-2026-01-000001 in_progress --sch=confirmed
     */

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:update-status {type} {id_or_code} {status} {--sch= : Status para o agendamento vinculado (apenas para service)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Altera rapidamente o status de um Budget, Service ou Schedule (use --sch para ambos)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $identifier = $this->argument('id_or_code');
        $status = $this->argument('status');
        $scheduleStatus = $this->option('sch');

        $this->info("Tentando atualizar {$type} ({$identifier}) para status: {$status}");

        switch (strtolower($type)) {
            case 'budget':
                $model = Budget::where('id', $identifier)->orWhere('code', $identifier)->first();
                break;
            case 'service':
                $model = Service::where('id', $identifier)->orWhere('code', $identifier)->first();

                // Se passou --sch, tenta atualizar o agendamento vinculado
                if ($model && $scheduleStatus) {
                    $schedule = Schedule::where('service_id', $model->id)->latest()->first();
                    if ($schedule) {
                        $schedule->update(['status' => $scheduleStatus]);
                        $this->info("Agendamento vinculado (ID: {$schedule->id}) atualizado para: {$scheduleStatus}");
                    } else {
                        $this->warn("Nenhum agendamento encontrado para este serviço.");
                    }
                }
                break;
            case 'schedule':
                // Se o identificador começar com SERV-, busca pelo código do serviço
                if (str_starts_with((string) $identifier, 'SERV-')) {
                    $service = Service::where('code', $identifier)->first();
                    if ($service) {
                        // Busca o agendamento mais recente vinculado a este serviço
                        $model = Schedule::where('service_id', $service->id)->latest()->first();
                        if ($model) {
                            $this->info("Encontrado agendamento ID: {$model->id} para o serviço {$identifier}");
                        }
                    } else {
                        $this->error("Serviço {$identifier} não encontrado.");
                        return 1;
                    }
                } else {
                    $model = Schedule::find($identifier);
                }
                break;
            default:
                $this->error("Tipo inválido. Use: budget, service ou schedule.");
                return 1;
        }

        if (!$model) {
            $this->error("Registro não encontrado.");
            return 1;
        }

        $model->update(['status' => $status]);

        $this->info("{$type} atualizado com sucesso para: {$status}");

        return 0;
    }
}
