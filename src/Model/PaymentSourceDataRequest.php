<?php

namespace LevelCredit\Tradeline\Model;

use LevelCredit\Tradeline\Model\SubModel\Address;
use LevelCredit\Tradeline\Model\SubModel\PaymentAccount;
use LevelCredit\Tradeline\Model\SubModel\PaymentAccountAddress;

class PaymentSourceDataRequest
{
    /**
     * @var PaymentAccount
     */
    protected $paymentAccount;

    /**
     * @var PaymentAccountAddress
     */
    protected $address;

    public function __construct(PaymentAccount $paymentAccount, PaymentAccountAddress $address)
    {
        $this->paymentAccount = $paymentAccount;
        $this->address = $address;
    }

    /**
     * @param PaymentAccount $paymentAccount
     * @param PaymentAccountAddress $address
     * @return static
     */
    public static function create(PaymentAccount $paymentAccount, PaymentAccountAddress $address): self
    {
        return new static($paymentAccount, $address);
    }

    /**
     * @return PaymentAccount
     */
    public function getPaymentAccount(): PaymentAccount
    {
        return $this->paymentAccount;
    }

    /**
     * @return PaymentAccountAddress
     */
    public function getAddress(): PaymentAccountAddress
    {
        return $this->address;
    }
}
