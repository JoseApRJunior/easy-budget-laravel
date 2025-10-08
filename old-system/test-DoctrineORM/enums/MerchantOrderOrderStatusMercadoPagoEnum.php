<?php

namespace app\enums;

enum MerchantOrderOrderStatusMercadoPagoEnum: string
{
    case payment_required = 'payment_required';
    case payment_in_process = 'payment_in_process';
    case reverted = 'reverted';
    case paid = 'paid';
    case partially_reverted = 'partially_reverted';
    case partially_paid = 'partially_paid';
    case partially_in_process = 'partially_in_process';
    case undefined = 'undefined';
    case expired = 'expired';
}
