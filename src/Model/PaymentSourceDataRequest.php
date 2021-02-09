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
