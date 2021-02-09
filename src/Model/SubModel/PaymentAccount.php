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

namespace LevelCredit\Tradeline\Model\SubModel;

use LevelCredit\Tradeline\Enum\PaymentAccountType;

abstract class PaymentAccount
{
    /**
     * @var string
     */
    protected $holderName;

    /**
     * @var string
     */
    protected $accountNumber;

    /**
     * @return string
     */
    public function getHolderName(): string
    {
        return $this->holderName;
    }

    /**
     * @return string
     */
    public function getAccountNumber(): string
    {
        return $this->accountNumber;
    }

    /**
     * Will return one of PaymentAccountType constants
     * @see PaymentAccountType
     * @return string
     */
    abstract public function getType(): string;
}
