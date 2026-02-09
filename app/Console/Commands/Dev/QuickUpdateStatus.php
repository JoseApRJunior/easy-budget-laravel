<?php

declare(strict_types=1);

namespace App\Console\Commands\Dev;

use App\Models\Budget;
use App\Models\Schedule;
use App\Models\Service;
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
                // Suporte a prefixos variados (BUD-, ORC-, etc.)
                $model = Budget::where('id', $identifier)->orWhere('code', $identifier)->first();
                if ($model) {
                    // Limpar histórico
                    $model->actionHistory()->delete();
                    $this->info('Histórico de ações limpo.');

                    // Tentar limpar notificações se a relação existir
                    try {
                        if (method_exists($model, 'notifications')) {
                            $model->notifications()->delete();
                            $this->info('Notificações limpas.');
                        }
                    } catch (\Exception $e) {
                        // Silenciosamente ignorar se a tabela não existir
                    }

                    // Preparar supressão de notificações
                    $model->suppressStatusNotification = true;

                    // Atualizar serviços vinculados
                    $services = $model->services;
                    foreach ($services as $service) {
                        // Tenta encontrar um status correspondente no ServiceStatus
                        try {
                            $serviceStatus = \App\Enums\ServiceStatus::tryFrom($status);
                            if ($serviceStatus) {
                                // Suprimir notificações para o serviço também
                                $service->suppressStatusNotification = true;
                                $service->update(['status' => $serviceStatus]);
                                $this->info("Serviço {$service->code} atualizado para: {$status}");

                                // Se o status for 'draft', removemos os agendamentos e faturas vinculados
                                if ($status === 'draft') {
                                    // Remover Agendamentos
                                    $deletedSchedules = \App\Models\Schedule::where('service_id', $service->id)->delete();
                                    if ($deletedSchedules > 0) {
                                        $this->info("Agendamentos ({$deletedSchedules}) do serviço {$service->code} removidos.");
                                    }

                                    // Remover Faturas (Invoices) e seus itens/compartilhamentos/pagamentos
                                    $invoices = \App\Models\Invoice::withTrashed()->where('service_id', $service->id)->get();
                                    foreach ($invoices as $invoice) {
                                        // Limpeza manual de relações para garantir remoção física se não houver cascade
                                        $invoice->invoiceItems()->delete();
                                        $invoice->shares()->delete();
                                        if (method_exists($invoice, 'paymentMercadoPagoInvoice')) {
                                            $invoice->paymentMercadoPagoInvoice()->delete();
                                        }

                                        $invoiceCode = $invoice->code;
                                        // Forçar exclusão física (ignora SoftDeletes)
                                        $invoice->forceDelete(); 
                                        $this->info("Fatura {$invoiceCode} do serviço {$service->code} removida fisicamente.");
                                    }

                                    // Limpar movimentações de estoque e restaurar quantidades
                                    $this->cleanupInventory($service);
                                } elseif (in_array($status, ['pending', 'cancelled'])) {
                                    // Para outros status de "reset", apenas atualizamos o status do agendamento
                                    $schedule = Schedule::where('service_id', $service->id)->latest()->first();
                                    if ($schedule) {
                                        $schedule->suppressStatusNotification = true;
                                        $schedule->update(['status' => $status]);
                                        $this->info("Agendamento do serviço {$service->code} atualizado para {$status}.");
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            $this->warn("Não foi possível atualizar status do serviço {$service->code}: " . $e->getMessage());
                        }
                    }
                }
                break;
            case 'service':
                $model = Service::where('id', $identifier)->orWhere('code', $identifier)->first();

                if ($model && $status === 'draft') {
                    $deletedCount = Schedule::where('service_id', $model->id)->delete();
                    if ($deletedCount > 0) {
                        $this->info("Agendamentos ({$deletedCount}) do serviço {$model->code} removidos.");
                    }

                    // Limpar movimentações de estoque e restaurar quantidades
                    $this->cleanupInventory($model);
                }

                // Se passou --sch, tenta atualizar o agendamento vinculado
                if ($model && $scheduleStatus) {
                    $schedule = Schedule::where('service_id', $model->id)->latest()->first();
                    if ($schedule) {
                        $schedule->suppressStatusNotification = true;
                        $schedule->update(['status' => $scheduleStatus]);
                        $this->info("Agendamento vinculado (ID: {$schedule->id}) atualizado para: {$scheduleStatus}");
                    } else {
                        $this->warn('Nenhum agendamento encontrado para este serviço.');
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
                $this->error('Tipo inválido. Use: budget, service ou schedule.');

                return 1;
        }

        if (! $model) {
            $this->error('Registro não encontrado.');

            return 1;
        }

        // Suprimir notificações para a atualização principal
        $model->suppressStatusNotification = true;
        $model->update(['status' => $status]);

        // Se o status for draft, garantimos a invalidação dos compartilhamentos
        if (strtolower($type) === 'budget' && $status === 'draft') {
            $model->refresh(); // Garante que temos a instância atualizada
            // $model->public_token e $model->public_expires_at foram removidos

            // Invalidamos quaisquer tokens de compartilhamento ativos na tabela budget_shares
            try {
                \App\Models\BudgetShare::where('budget_id', $model->id)
                    ->update([
                        'is_active' => false,
                        'status' => 'expired',
                        'expires_at' => now()
                    ]);
                $this->info('Tokens de compartilhamento (budget_shares) invalidados.');
            } catch (\Exception $e) {
                $this->warn('Não foi possível invalidar budget_shares: ' . $e->getMessage());
            }

            $this->info('Compartilhamentos (budget_shares) invalidados com sucesso.');
        }

        $this->info("{$type} atualizado com sucesso para: {$status}");

        return 0;
    }

    /**
     * Limpa movimentações de estoque e restaura quantidades ao resetar um serviço.
     */
    protected function cleanupInventory(Service $service): void
    {
        $this->info("Limpando movimentações de estoque para o serviço {$service->code}...");

        $service->load('serviceItems');
        $currentStatus = $service->getOriginal('status');
        $currentStatusValue = $currentStatus instanceof \UnitEnum ? $currentStatus->value : (string) $currentStatus;

        foreach ($service->serviceItems as $item) {
            if (!$item->product_id) continue;

            $inventory = \App\Models\ProductInventory::where('product_id', $item->product_id)
                ->where('tenant_id', $service->tenant_id)
                ->first();

            if (!$inventory) continue;

            // 1. Restaurar estoque físico se houve consumo (status era IN_PROGRESS ou posterior)
            $movements = \App\Models\InventoryMovement::where('reference_type', \App\Models\ServiceItem::class)
                ->where('reference_id', $item->id)
                ->where('type', 'exit')
                ->get();

            foreach ($movements as $movement) {
                $inventory->increment('quantity', $movement->quantity);
                $this->info("- Restaurado {$movement->quantity} unid. de '{$inventory->product->name}' (Consumo desfeito).");
                $movement->delete();
            }

            // 2. Liberar reserva se o status atual era PREPARING
            // Como não temos log de reserva por ID, baseamos na quantidade do item se o status era PREPARING
            if ($currentStatusValue === \App\Enums\ServiceStatus::PREPARING->value) {
                if ($inventory->reserved_quantity >= $item->quantity) {
                    $inventory->decrement('reserved_quantity', (int) $item->quantity);
                    $this->info("- Liberada reserva de {$item->quantity} unid. de '{$inventory->product->name}'.");
                }
            }
        }
    }
}
