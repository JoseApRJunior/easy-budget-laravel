<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\DTOs\Customer\CustomerInteractionDTO;
use App\Models\Customer;
use App\Models\CustomerInteraction;
use App\Models\User;
use App\Repositories\CustomerInteractionRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Interações com Clientes - Timeline e histórico
 *
 * Gerencia todas as interações com clientes, incluindo criação,
 * atualização, lembretes automáticos e notificações.
 */
class CustomerInteractionService extends AbstractBaseService
{
    private CustomerInteractionRepository $interactionRepository;

    public function __construct(CustomerInteractionRepository $interactionRepository)
    {
        parent::__construct($interactionRepository);
        $this->interactionRepository = $interactionRepository;
    }

    /**
     * Cria uma nova interação com cliente.
     */
    public function createInteraction(Customer $customer, CustomerInteractionDTO $dto, User $user): ServiceResult
    {
        return $this->safeExecute(function () use ($customer, $dto, $user) {
            return DB::transaction(function () use ($customer, $dto, $user) {
                $interaction = $this->interactionRepository->createFromDTO($customer->id, $user->id, $dto);

                // Atualizar contador de interações do cliente
                $customer->increment('total_interactions');
                $customer->update(['last_interaction_at' => now()]);

                // Criar lembrete se houver próxima ação
                if ($interaction->next_action && $interaction->next_action_date) {
                    $this->createReminder($interaction, $user);
                }

                // Notificar cliente se necessário
                if ($dto->notify_customer) {
                    $this->notifyCustomer($interaction, $customer);
                }

                // Log da interação
                Log::info('Interação criada', [
                    'interaction_id' => $interaction->id,
                    'customer_id' => $customer->id,
                    'user_id' => $user->id,
                    'type' => $interaction->type,
                ]);

                return $interaction;
            });
        }, 'Erro ao criar interação.');
    }

    /**
     * Atualiza uma interação existente.
     */
    public function updateInteraction(int $id, CustomerInteractionDTO $dto, User $user): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $dto, $user) {
            return DB::transaction(function () use ($id, $dto, $user) {
                $interaction = $this->interactionRepository->find($id);

                if (!$interaction) {
                    return $this->error('Interação não encontrada.');
                }

                $oldNextAction = $interaction->next_action;
                $oldNextActionDate = $interaction->next_action_date;

                $interaction = $this->interactionRepository->updateFromDTO($id, $dto);

                // Gerenciar lembretes se próxima ação foi alterada
                if (
                    ($oldNextAction !== $interaction->next_action) ||
                    ($oldNextActionDate !== $interaction->next_action_date)
                ) {
                    // Remover lembretes antigos
                    $this->removeReminders($interaction);

                    // Criar novo lembrete se necessário
                    if ($interaction->next_action && $interaction->next_action_date) {
                        $this->createReminder($interaction, $user);
                    }
                }

                Log::info('Interação atualizada', [
                    'interaction_id' => $interaction->id,
                    'user_id' => $user->id,
                ]);

                return $interaction;
            });
        }, 'Erro ao atualizar interação.');
    }

    /**
     * Busca interações de um cliente com filtros.
     */
    public function getCustomerInteractions(int $customerId, array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($customerId, $filters) {
            return $this->interactionRepository->getPaginatedByCustomer($customerId, $filters);
        });
    }

    /**
     * Obtém timeline de interações para dashboard.
     */
    public function getInteractionsTimeline(User $user, int $days = 30): ServiceResult
    {
        return $this->safeExecute(function () use ($user, $days) {
            return $this->interactionRepository->getTimeline($user->id, $days);
        });
    }

    /**
     * Obtém próximas ações pendentes para um usuário.
     */
    public function getPendingActions(User $user, int $limit = 20): ServiceResult
    {
        return $this->safeExecute(function () use ($user, $limit) {
            return $this->interactionRepository->getPendingActions($user->id, $limit);
        });
    }

    /**
     * Marca interação como concluída.
     */
    public function completeInteraction(int $id, User $user): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $user) {
            $interaction = $this->interactionRepository->find($id);

            if (!$interaction) {
                return $this->error('Interação não encontrada.');
            }

            $interaction->update(['outcome' => 'completed']);

            Log::info('Interação marcada como concluída', [
                'interaction_id' => $interaction->id,
                'user_id' => $user->id,
            ]);

            return $interaction;
        });
    }

    /**
     * Cancela uma interação.
     */
    public function cancelInteraction(int $id, User $user): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $user) {
            $interaction = $this->interactionRepository->find($id);

            if (!$interaction) {
                return $this->error('Interação não encontrada.');
            }

            $interaction->update(['outcome' => 'cancelled']);

            // Remover lembretes associados
            $this->removeReminders($interaction);

            Log::info('Interação cancelada', [
                'interaction_id' => $interaction->id,
                'user_id' => $user->id,
            ]);

            return $interaction;
        });
    }

    /**
     * Reagenda próxima ação de uma interação.
     */
    public function rescheduleInteraction(
        int $id,
        string $nextAction,
        $nextActionDate,
        User $user,
    ): ServiceResult {
        return $this->safeExecute(function () use ($id, $nextAction, $nextActionDate, $user) {
            $interaction = $this->interactionRepository->find($id);

            if (!$interaction) {
                return $this->error('Interação não encontrada.');
            }

            $interaction->update([
                'next_action' => $nextAction,
                'next_action_date' => $nextActionDate,
                'outcome' => 'rescheduled',
            ]);

            Log::info('Próxima ação reagendada', [
                'interaction_id' => $interaction->id,
                'next_action' => $nextAction,
                'next_action_date' => $nextActionDate,
                'user_id' => $user->id,
            ]);

            return $interaction;
        });
    }

    /**
     * Obtém estatísticas de interações.
     */
    public function getInteractionStats(User $user): ServiceResult
    {
        return $this->safeExecute(function () use ($user) {
            $baseQuery = CustomerInteraction::where('user_id', $user->id);

            return [
                'total_interactions' => (clone $baseQuery)->count(),
                'interactions_today' => (clone $baseQuery)->whereDate('interaction_date', today())->count(),
                'interactions_this_week' => (clone $baseQuery)->whereBetween('interaction_date', [
                    now()->startOfWeek(), now()->endOfWeek(),
                ])->count(),
                'interactions_this_month' => (clone $baseQuery)->whereMonth('interaction_date', now()->month)->count(),
                'pending_actions' => (clone $baseQuery)->pendingActions()->count(),
                'overdue_actions' => (clone $baseQuery)->whereNotNull('next_action_date')
                    ->where('next_action_date', '<', now())
                    ->where(function ($q) {
                        $q->whereNull('outcome')
                            ->orWhere('outcome', '!=', 'completed');
                    })->count(),
                'interactions_by_type' => (clone $baseQuery)->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
            ];
        });
    }

    /**
     * Cria lembrete para próxima ação.
     */
    private function createReminder(CustomerInteraction $interaction, User $user): void
    {
        try {
            // Criar evento no sistema de lembretes (se existir)
            // Por ora, apenas logamos a criação do lembrete
            Log::info('Lembrete criado para interação', [
                'interaction_id' => $interaction->id,
                'next_action' => $interaction->next_action,
                'next_action_date' => $interaction->next_action_date,
                'user_id' => $user->id,
            ]);

            // TODO: Integrar com sistema de lembretes/notificações
        } catch (\Exception $e) {
            Log::error('Erro ao criar lembrete', [
                'interaction_id' => $interaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove lembretes de uma interação.
     */
    private function removeReminders(CustomerInteraction $interaction): void
    {
        try {
            // Remover lembretes associados (se existir)
            Log::info('Lembretes removidos para interação', [
                'interaction_id' => $interaction->id,
            ]);

            // TODO: Integrar com sistema de lembretes
        } catch (\Exception $e) {
            Log::error('Erro ao remover lembretes', [
                'interaction_id' => $interaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notifica cliente sobre interação.
     */
    private function notifyCustomer(CustomerInteraction $interaction, Customer $customer): void
    {
        try {
            // Buscar email principal do cliente
            $primaryEmail = $customer->primary_email;

            if (!$primaryEmail) {
                Log::warning('Cliente sem email para notificação', [
                    'interaction_id' => $interaction->id,
                    'customer_id' => $customer->id,
                ]);

                return;
            }

            // TODO: Implementar sistema de notificações por email
            // Por ora, apenas logamos
            Log::info('Notificação de cliente enviada', [
                'interaction_id' => $interaction->id,
                'customer_id' => $customer->id,
                'email' => $primaryEmail,
                'type' => $interaction->type,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao notificar cliente', [
                'interaction_id' => $interaction->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtém tipos de interação disponíveis.
     */
    public function getInteractionTypes(): array
    {
        return [
            'call' => 'Ligação',
            'email' => 'Email',
            'meeting' => 'Reunião',
            'visit' => 'Visita',
            'proposal' => 'Proposta',
            'note' => 'Nota',
            'task' => 'Tarefa',
        ];
    }

    /**
     * Obtém direções de interação disponíveis.
     */
    public function getInteractionDirections(): array
    {
        return [
            'inbound' => 'Entrada (cliente iniciou)',
            'outbound' => 'Saída (nós iniciamos)',
        ];
    }

    /**
     * Obtém outcomes de interação disponíveis.
     */
    public function getInteractionOutcomes(): array
    {
        return [
            'completed' => 'Concluída',
            'pending' => 'Pendente',
            'cancelled' => 'Cancelada',
            'rescheduled' => 'Reagendada',
        ];
    }

    /**
     * Gera relatório de interações por período.
     */
    public function generateInteractionReport(User $user, array $filters): ServiceResult
    {
        return $this->safeExecute(function () use ($user, $filters) {
            $query = CustomerInteraction::where('user_id', $user->id)
                ->with(['customer']);

            // Aplicar filtros de período
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $query->inDateRange($filters['start_date'], $filters['end_date']);
            }

            $interactions = $query->get();

            return [
                'total_interactions' => $interactions->count(),
                'interactions_by_type' => $interactions->groupBy('type')->map->count(),
                'interactions_by_outcome' => $interactions->groupBy('outcome')->map->count(),
                'interactions_by_direction' => $interactions->groupBy('direction')->map->count(),
                'average_duration' => $interactions->whereNotNull('duration_minutes')->avg('duration_minutes'),
                'customers_interacted' => $interactions->pluck('customer')->unique('id')->count(),
                'pending_follow_ups' => $interactions->whereNotNull('next_action')->count(),
            ];
        });
    }

    /**
     * Busca interações que precisam de follow-up hoje.
     */
    public function getTodayFollowUps(User $user): ServiceResult
    {
        return $this->safeExecute(function () use ($user) {
            return CustomerInteraction::where('user_id', $user->id)
                ->whereNotNull('next_action')
                ->whereNotNull('next_action_date')
                ->whereDate('next_action_date', today())
                ->where(function ($query) {
                    $query->whereNull('outcome')
                        ->orWhere('outcome', '!=', 'completed');
                })
                ->with(['customer'])
                ->orderBy('next_action_date')
                ->get();
        });
    }

    /**
     * Busca interações em atraso.
     */
    public function getOverdueInteractions(User $user): ServiceResult
    {
        return $this->safeExecute(function () use ($user) {
            return CustomerInteraction::where('user_id', $user->id)
                ->whereNotNull('next_action_date')
                ->where('next_action_date', '<', now())
                ->where(function ($query) {
                    $query->whereNull('outcome')
                        ->orWhere('outcome', '!=', 'completed');
                })
                ->with(['customer'])
                ->orderBy('next_action_date')
                ->get();
        });
    }

    /**
     * Remove uma interação.
     */
    public function deleteInteraction(int $id, User $user): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $user) {
            return DB::transaction(function () use ($id, $user) {
                $interaction = $this->interactionRepository->find($id);

                if (!$interaction) {
                    return $this->error('Interação não encontrada.');
                }

                // Remover lembretes associados
                $this->removeReminders($interaction);

                // Marcar interação como inativa
                $interaction->update(['is_active' => false]);

                // Decrementar contador do cliente
                $interaction->customer->decrement('total_interactions');

                Log::info('Interação removida', [
                    'interaction_id' => $interaction->id,
                    'user_id' => $user->id,
                ]);

                return true;
            });
        });
    }

    /**
     * Duplica uma interação para reutilização.
     */
    public function duplicateInteraction(int $id, User $user): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $user) {
            return DB::transaction(function () use ($id, $user) {
                $interaction = $this->interactionRepository->find($id);

                if (!$interaction) {
                    return $this->error('Interação não encontrada.');
                }

                $newInteraction = $interaction->replicate();
                $newInteraction->interaction_date = now();
                $newInteraction->outcome = null;
                $newInteraction->next_action_date = null;
                $newInteraction->is_active = true;
                $newInteraction->save();

                Log::info('Interação duplicada', [
                    'original_interaction_id' => $interaction->id,
                    'new_interaction_id' => $newInteraction->id,
                    'user_id' => $user->id,
                ]);

                return $newInteraction;
            });
        });
    }
}
