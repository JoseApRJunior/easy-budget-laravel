<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Models\Customer;
use App\Models\CustomerInteraction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Serviço de Interações com Clientes - Timeline e histórico
 *
 * Gerencia todas as interações com clientes, incluindo criação,
 * atualização, lembretes automáticos e notificações.
 */
class CustomerInteractionService
{
    /**
     * Cria uma nova interação com cliente.
     */
    public function createInteraction(Customer $customer, array $data, User $user): CustomerInteraction
    {
        return DB::transaction(function () use ($customer, $data, $user) {
            $interaction = new CustomerInteraction([
                'user_id' => $user->id,
                'type' => $data['type'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'direction' => $data['direction'] ?? 'outbound',
                'interaction_date' => $data['interaction_date'] ?? now(),
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'outcome' => $data['outcome'] ?? null,
                'next_action' => $data['next_action'] ?? null,
                'next_action_date' => $data['next_action_date'] ?? null,
                'attachments' => $data['attachments'] ?? [],
                'metadata' => $data['metadata'] ?? [],
                'notify_customer' => $data['notify_customer'] ?? false,
                'is_active' => true,
            ]);

            $interaction->customer()->associate($customer);
            $interaction->save();

            // Atualizar contador de interações do cliente
            $customer->increment('total_interactions');
            $customer->update(['last_interaction_at' => now()]);

            // Criar lembrete se houver próxima ação
            if ($interaction->next_action && $interaction->next_action_date) {
                $this->createReminder($interaction, $user);
            }

            // Notificar cliente se necessário
            if ($data['notify_customer'] ?? false) {
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
    }

    /**
     * Atualiza uma interação existente.
     */
    public function updateInteraction(CustomerInteraction $interaction, array $data, User $user): CustomerInteraction
    {
        return DB::transaction(function () use ($interaction, $data, $user) {
            $oldNextAction = $interaction->next_action;
            $oldNextActionDate = $interaction->next_action_date;

            $interaction->update([
                'type' => $data['type'] ?? $interaction->type,
                'title' => $data['title'] ?? $interaction->title,
                'description' => $data['description'] ?? $interaction->description,
                'direction' => $data['direction'] ?? $interaction->direction,
                'interaction_date' => $data['interaction_date'] ?? $interaction->interaction_date,
                'duration_minutes' => $data['duration_minutes'] ?? $interaction->duration_minutes,
                'outcome' => $data['outcome'] ?? $interaction->outcome,
                'next_action' => $data['next_action'] ?? $interaction->next_action,
                'next_action_date' => $data['next_action_date'] ?? $interaction->next_action_date,
                'attachments' => $data['attachments'] ?? $interaction->attachments,
                'metadata' => $data['metadata'] ?? $interaction->metadata,
                'notify_customer' => $data['notify_customer'] ?? $interaction->notify_customer,
            ]);

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

            return $interaction->fresh();
        });
    }

    /**
     * Busca interações de um cliente com filtros.
     */
    public function getCustomerInteractions(Customer $customer, array $filters = []): LengthAwarePaginator
    {
        $query = $customer->interactions()
            ->with(['user'])
            ->orderBy('interaction_date', 'desc');

        // Filtro por tipo
        if (! empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        // Filtro por direção
        if (! empty($filters['direction'])) {
            $query->ofDirection($filters['direction']);
        }

        // Filtro por período
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->inDateRange($filters['start_date'], $filters['end_date']);
        }

        // Filtro por usuário
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filtro por próximas ações pendentes
        if (! empty($filters['pending_actions'])) {
            $query->pendingActions();
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Obtém timeline de interações para dashboard.
     */
    public function getInteractionsTimeline(User $user, int $days = 30): Collection
    {
        return CustomerInteraction::where('user_id', $user->id)
            ->where('interaction_date', '>=', now()->subDays($days))
            ->with(['customer'])
            ->orderBy('interaction_date', 'desc')
            ->limit(50)
            ->get();
    }

    /**
     * Obtém próximas ações pendentes para um usuário.
     */
    public function getPendingActions(User $user, int $limit = 20): Collection
    {
        return CustomerInteraction::where('user_id', $user->id)
            ->pendingActions()
            ->with(['customer'])
            ->orderBy('next_action_date', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Marca interação como concluída.
     */
    public function completeInteraction(CustomerInteraction $interaction, User $user): void
    {
        $interaction->markAsCompleted();

        Log::info('Interação marcada como concluída', [
            'interaction_id' => $interaction->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Cancela uma interação.
     */
    public function cancelInteraction(CustomerInteraction $interaction, User $user): void
    {
        $interaction->markAsCancelled();

        // Remover lembretes associados
        $this->removeReminders($interaction);

        Log::info('Interação cancelada', [
            'interaction_id' => $interaction->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Reagenda próxima ação de uma interação.
     */
    public function rescheduleInteraction(
        CustomerInteraction $interaction,
        string $nextAction,
        $nextActionDate,
        User $user,
    ): void {
        $interaction->rescheduleNextAction($nextAction, $nextActionDate);

        Log::info('Próxima ação reagendada', [
            'interaction_id' => $interaction->id,
            'next_action' => $nextAction,
            'next_action_date' => $nextActionDate,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Obtém estatísticas de interações.
     */
    public function getInteractionStats(User $user): array
    {
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
            // Reminder::create([
            //     'user_id' => $user->id,
            //     'title' => "Follow-up: {$interaction->next_action}",
            //     'description' => "Cliente: {$interaction->customer->name}",
            //     'reminder_date' => $interaction->next_action_date,
            //     'type' => 'customer_interaction',
            //     'related_id' => $interaction->id,
            //     'is_active' => true
            // ]);

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
            // Reminder::where('type', 'customer_interaction')
            //         ->where('related_id', $interaction->id)
            //         ->delete();

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

            if (! $primaryEmail) {
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

            // Exemplo de implementação futura:
            // Notification::route('mail', $primaryEmail)
            //             ->notify(new CustomerInteractionNotification($interaction));

        } catch (\Exception $e) {
            Log::error('Erro ao notificar cliente', [
                'interaction_id' => $interaction->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Valida dados de interação.
     */
    public function validateInteractionData(array $data): array
    {
        $errors = [];

        if (empty($data['type'])) {
            $errors[] = 'Tipo de interação é obrigatório.';
        }

        if (empty($data['title'])) {
            $errors[] = 'Título da interação é obrigatório.';
        }

        if (empty($data['interaction_date'])) {
            $errors[] = 'Data da interação é obrigatória.';
        }

        if (! empty($data['next_action_date']) && ! empty($data['interaction_date'])) {
            $interactionDate = strtotime($data['interaction_date']);
            $nextActionDate = strtotime($data['next_action_date']);

            if ($nextActionDate <= $interactionDate) {
                $errors[] = 'Data da próxima ação deve ser posterior à data da interação.';
            }
        }

        if (! empty($data['duration_minutes']) && $data['duration_minutes'] <= 0) {
            $errors[] = 'Duração deve ser maior que zero.';
        }

        return $errors;
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
    public function generateInteractionReport(User $user, array $filters): array
    {
        $query = CustomerInteraction::where('user_id', $user->id)
            ->with(['customer']);

        // Aplicar filtros de período
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
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
    }

    /**
     * Busca interações que precisam de follow-up hoje.
     */
    public function getTodayFollowUps(User $user): Collection
    {
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
    }

    /**
     * Busca interações em atraso.
     */
    public function getOverdueInteractions(User $user): Collection
    {
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
    }

    /**
     * Remove uma interação.
     */
    public function deleteInteraction(CustomerInteraction $interaction, User $user): bool
    {
        return DB::transaction(function () use ($interaction, $user) {
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
    }

    /**
     * Duplica uma interação para reutilização.
     */
    public function duplicateInteraction(CustomerInteraction $interaction, User $user): CustomerInteraction
    {
        return DB::transaction(function () use ($interaction, $user) {
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
    }
}
