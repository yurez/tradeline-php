<?php

namespace LevelCredit\Tradeline\Enum;

class OrderStatus
{
    // successful statuses
    public const PENDING = 'pending';
    public const COMPLETE = 'complete';

    // failed statuses
    public const NEWONE = 'new';
    public const ERROR = 'error';

    // cancellation statuses
    public const CANCELLED = 'cancelled';
    public const REFUNDED = 'refunded';
    public const RETURNED = 'returned';
}
