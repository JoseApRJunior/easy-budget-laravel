<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Budget;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event disparado quando o status de um orçamento é alterado.
 */
class BudgetStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Budget $budget;

    public string $oldStatus;

    public string $newStatus;

    public ?string $comment;

    public bool $suppressNotification;

    public function __construct(Budget $budget, string $oldStatus, string $newStatus, ?string $comment = null, bool $suppressNotification = false)
    {
        $this->budget = $budget;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->comment = $comment;
        $this->suppressNotification = $suppressNotification;
    }
}
