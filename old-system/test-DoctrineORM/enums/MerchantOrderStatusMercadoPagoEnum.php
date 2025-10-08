<?php

namespace app\enums;

enum MerchantOrderStatusMercadoPagoEnum: string
{
    case opened = 'opened';
    case closed = 'closed';
    case expired = 'expired';
}
