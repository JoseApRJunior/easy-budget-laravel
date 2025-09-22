<?php

namespace app\enums;

enum PaymentStatusMercadoPagoEnum: string
{
    case approved = 'approved';
    case pending = 'pending';
    case authorized = 'authorized';
    case in_process = 'in_process';
    case in_mediation = 'in_mediation';
    case rejected = 'rejected';
    case cancelled = 'cancelled';
    case refunded = 'refunded';
    case charged_back = 'charged_back';
    case recovered = 'recovered';
}
