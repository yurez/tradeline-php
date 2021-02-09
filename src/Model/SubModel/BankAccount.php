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

use LevelCredit\Tradeline\Enum\BankAccountType;
use LevelCredit\Tradeline\Enum\PaymentAccountType;

class BankAccount extends PaymentAccount
{
    /**
     * @var string
     */
    protected $routingNumber;

    /**
     * @var string
     */
    protected $bankAccountType;

    /**
     * @param string $holderName
     * @param string $accountNumber
     * @param string $routingNumber
     * @param string $bankAccountType
     * @see BankAccountType for check supported types
     */
    public function __construct(
        string $holderName,
        string $accountNumber,
        string $routingNumber,
        string $bankAccountType
    ) {
        $this->holderName = $holderName;
        $this->accountNumber = $accountNumber;
        $this->routingNumber = $routingNumber;
        $this->bankAccountType = $bankAccountType;
    }

    /**
     * @param string $holderName
     * @param string $accountNumber
     * @param string $routingNumber
     * @param string $bankAccountType
     * @return static
     */
    public static function create(
        string $holderName,
        string $accountNumber,
        string $routingNumber,
        string $bankAccountType
    ): self {
        return new static($holderName, $accountNumber, $routingNumber, $bankAccountType);
    }

    /**
     * @return string
     */
    public function getRoutingNumber(): string
    {
        return $this->routingNumber;
    }

    /**
     * @return string
     */
    public function getBankAccountType(): string
    {
        return $this->bankAccountType;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return PaymentAccountType::BANK;
    }
}
