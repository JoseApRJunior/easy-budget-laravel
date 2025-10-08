<?php

namespace app\enums;

enum PaymentStatusEnum: string
{
    case approved         = 'approved';
    case pending          = 'pending';
    case rejected         = 'rejected';
    case canceled         = 'cancelled';
    case refunded         = 'refunded';
    case partial_refunded = 'partial_refunded';
    case charged_back     = 'charged_back';
    case in_mediation     = 'in_mediation';
}
