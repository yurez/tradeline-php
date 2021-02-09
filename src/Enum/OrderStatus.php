<?php

/* Copyright(c) 2021 by RentTrack, Inc.  All rights reserved.
 *
 * This software contains proprietary and confidential information of
 * RentTrack Inc., and its suppliers.  Except as may be set forth
 * in the license agreement under which this software is supplied, use,
 * disclosure, or  reproduction is prohibited without the prior express
 * written consent of RentTrack, Inc.
 *
 * The license terms of service are hosted at https://github.com/levelcredit/tradeline-php/blob/master/LICENSE
 */

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
