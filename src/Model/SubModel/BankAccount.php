<?php

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
