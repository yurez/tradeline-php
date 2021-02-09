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

abstract class CardAccount extends PaymentAccount
{
    /**
     * @var \DateTimeInterface
     */
    protected $expirationDate;

    /**
     * @var string
     */
    protected $securityCode;

    /**
     * @return \DateTimeInterface
     */
    public function getExpirationDate(): \DateTimeInterface
    {
        return $this->expirationDate;
    }

    /**
     * @return string
     */
    public function getSecurityCode(): string
    {
        return $this->securityCode;
    }
}
