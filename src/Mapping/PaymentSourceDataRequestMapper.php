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

namespace LevelCredit\Tradeline\Mapping;

use LevelCredit\LevelCreditApi\Enum\BankAccountType as ApiBankAccountType;
use LevelCredit\LevelCreditApi\Enum\PaymentAccountType as ApiPaymentAccountType;
use LevelCredit\LevelCreditApi\Model\Request\BankAccount as ApiBankAccount;
use LevelCredit\LevelCreditApi\Model\Request\CardAccount as ApiCardAccount;
use LevelCredit\LevelCreditApi\Model\Request\PaymentSource;
use LevelCredit\LevelCreditApi\Model\Request\PaymentAccountAddress as ApiPaymentAccountAddress;
use LevelCredit\Tradeline\Enum\BankAccountType;
use LevelCredit\Tradeline\Enum\PaymentAccountType;
use LevelCredit\Tradeline\Model\PaymentSourceDataRequest;
use LevelCredit\Tradeline\Model\SubModel\BankAccount;
use LevelCredit\Tradeline\Model\SubModel\CreditCardAccount;
use LevelCredit\Tradeline\Model\SubModel\DebitCardAccount;
use LevelCredit\Tradeline\Model\SubModel\PaymentAccount;
use LevelCredit\Tradeline\Model\SubModel\PaymentAccountAddress;
use function preg_replace;

class PaymentSourceDataRequestMapper
{
    /**
     * @param PaymentSourceDataRequest $request
     * @return PaymentSource
     */
    public static function map(PaymentSourceDataRequest $request): PaymentSource
    {
        $paymentSource = PaymentSource::create()
            ->setName($request->getPaymentAccount()->getHolderName())
            ->setType(static::mapPaymentAccountType($request->getPaymentAccount()->getType()))
            ->setAddress(static::mapPaymentAccountAddress($request->getAddress()));

        return static::mapPaymentSourceData($paymentSource, $request->getPaymentAccount());
    }

    /**
     * @param string $type
     * @return string
     */
    protected static function mapPaymentAccountType(string $type): string
    {
        switch ($type) {
            case PaymentAccountType::BANK:
                return ApiPaymentAccountType::BANK;
            case PaymentAccountType::CREDIT_CARD:
                return ApiPaymentAccountType::CARD;
            case PaymentAccountType::DEBIT_CARD:
                return ApiPaymentAccountType::DEBIT_CARD;
            default:
                return '';
        }
    }

    /**
     * @param PaymentAccountAddress $address
     * @return ApiPaymentAccountAddress
     */
    protected static function mapPaymentAccountAddress(PaymentAccountAddress $address): ApiPaymentAccountAddress
    {
        return ApiPaymentAccountAddress::create()
            ->setStreet($address->getStreet())
            ->setCity($address->getCity())
            ->setState($address->getState())
            ->setZip($address->getZip());
    }

    /**
     * @param PaymentSource $paymentSource
     * @param PaymentAccount $paymentAccount
     * @return PaymentSource
     */
    protected static function mapPaymentSourceData(
        PaymentSource $paymentSource,
        PaymentAccount $paymentAccount
    ): PaymentSource {
        switch ($paymentAccount) {
            case $paymentAccount instanceof BankAccount:
                $paymentSource->setBank(
                    ApiBankAccount::create()
                        ->setAccount(preg_replace('/[^0-9]/', '', $paymentAccount->getAccountNumber()))
                        ->setRouting($paymentAccount->getRoutingNumber())
                        ->setType(static::mapBankAccountType($paymentAccount->getBankAccountType()))
                );
                break;
            case $paymentAccount instanceof CreditCardAccount:
            case $paymentAccount instanceof DebitCardAccount:
                $apiCardAccount = ApiCardAccount::create()
                    ->setAccount(preg_replace('/[^0-9]/', '', $paymentAccount->getAccountNumber()))
                    ->setExpiration($paymentAccount->getExpirationDate()->format('Y-m'))
                    ->setCvv($paymentAccount->getSecurityCode());
                if ($paymentAccount->getType() === PaymentAccountType::CREDIT_CARD) {
                    $paymentSource->setCard($apiCardAccount);
                } else {
                    $paymentSource->setDebitCard($apiCardAccount);
                }
                break;
        }

        return $paymentSource;
    }

    /**
     * @param string $type
     * @return string
     */
    protected static function mapBankAccountType(string $type): string
    {
        switch ($type) {
            case BankAccountType::CHECKING:
                return ApiBankAccountType::CHECKING;
            case BankAccountType::SAVINGS:
                return ApiBankAccountType::SAVINGS;
            default:
                return '';
        }
    }
}
