<?php

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
