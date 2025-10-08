<?php

namespace app\enums;

enum InvoiceStatusEnum: string
{
    case paid = 'paid';
    case pending = 'pending';
    case cancelled = 'cancelled';
    case overdue = 'overdue';
}
