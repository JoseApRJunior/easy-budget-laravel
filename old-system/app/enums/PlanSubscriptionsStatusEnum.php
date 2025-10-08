<?php

namespace app\enums;

enum PlanSubscriptionsStatusEnum: string
{
    case active = 'active';
    case pending = 'pending';
    case cancelled = 'cancelled';
    case expired = 'expired';
}
