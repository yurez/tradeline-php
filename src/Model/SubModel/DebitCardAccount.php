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

class DebitCardAccount extends CardAccount
{
    /**
     * @param string $holderName
     * @param string $cardNumber
     * @param \DateTimeInterface $expirationDate
     * @param string $securityCode
     */
    public function __construct(
        string $holderName,
        string $cardNumber,
        \DateTimeInterface $expirationDate,
        string $securityCode = ''
    ) {
        $this->holderName = $holderName;
        $this->accountNumber = $cardNumber;
        $this->expirationDate = $expirationDate;
        $this->securityCode = $securityCode;
    }

    /**
     * @param string $holderName
     * @param string $cardNumber
     * @param \DateTimeInterface $expirationDate
     * @param string $securityCode
     * @return static
     */
    public static function create(
        string $holderName,
        string $cardNumber,
        \DateTimeInterface $expirationDate,
        string $securityCode = ''
    ): self {
        return new static($holderName, $cardNumber, $expirationDate, $securityCode);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return PaymentAccountType::DEBIT_CARD;
    }
}
