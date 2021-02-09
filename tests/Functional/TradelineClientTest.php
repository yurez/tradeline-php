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

namespace LevelCredit\Tradeline\Tests\Functional;

use Dotenv\Dotenv;
use Faker\Generator as FakerGenerator;
use Faker\Factory as FakerFactory;
use LevelCredit\Tradeline\Enum\BankAccountType;
use LevelCredit\Tradeline\Enum\OrderStatus;
use LevelCredit\Tradeline\Enum\PaymentAccountType;
use LevelCredit\Tradeline\Model\PaymentSourceDataRequest;
use LevelCredit\Tradeline\Model\SubModel\BankAccount;
use LevelCredit\Tradeline\Model\SubModel\CreditCardAccount;
use LevelCredit\Tradeline\Model\SubModel\PaymentAccount;
use LevelCredit\Tradeline\Model\SubModel\PaymentAccountAddress;
use LevelCredit\Tradeline\TradelineClient;
use PHPUnit\Framework\TestCase;

class TradelineClientTest extends TestCase
{
    /**
     * @var FakerGenerator
     */
    protected $fakeData;

    public function setUp(): void
    {
        $this->loadEnvironment();
        $this->fakeData = FakerFactory::create();
        parent::setUp();
    }

    /**
     * @test
     */
    public function authenticateByUsernamePassword(): string
    {
        $authResponse = $this->getTradelineClient()->authenticate($_ENV['PARTNER_USERNAME'], $_ENV['PARTNER_PASSWORD']);

        $this->assertNotEmpty($authResponse->getAccessToken());
        $this->assertNotEmpty($authResponse->getRefreshToken());

        return $authResponse->getRefreshToken();
    }

    /**
     * @test
     * @depends authenticateByUsernamePassword
     */
    public function authenticateByRefreshToken(string $refreshToken): string
    {
        $authResponse = $this->getTradelineClient()->authenticate($refreshToken);

        $this->assertNotEmpty($authResponse->getAccessToken());
        $this->assertNotEmpty($authResponse->getRefreshToken());
        $this->assertNotEquals($refreshToken, $authResponse->getRefreshToken());

        return $authResponse->getAccessToken();
    }

    /**
     * @test
     * @depends authenticateByRefreshToken
     */
    public function purchaseBackreportingBankAccount($accessToken): void
    {
        $orderResponse = $this->getTradelineClient()->purchaseBackreporting(
            $accessToken,
            $this->prepareSyncDataJson(),
            $this->preparePaymentSourceDataRequest(PaymentAccountType::BANK)
        );

        $this->assertNotEmpty($orderResponse->getId());
        $this->assertNotEmpty($orderResponse->getReferenceId());
        $this->assertNotEmpty($orderResponse->getAmount());
        $this->assertEquals(OrderStatus::COMPLETE, $orderResponse->getStatus());
    }

    /**
     * @test
     * @depends authenticateByRefreshToken
     */
    public function purchaseBackreportingCreditCardAccount($accessToken): void
    {
        $orderResponse = $this->getTradelineClient()->purchaseBackreporting(
            $accessToken,
            $this->prepareSyncDataJson(),
            $this->preparePaymentSourceDataRequest(PaymentAccountType::CREDIT_CARD)
        );

        $this->assertNotEmpty($orderResponse->getId());
        $this->assertNotEmpty($orderResponse->getReferenceId());
        $this->assertNotEmpty($orderResponse->getAmount());
        $this->assertEquals(OrderStatus::COMPLETE, $orderResponse->getStatus());
    }

    protected function prepareSyncDataJson(): string
    {
        $startAt = (new \DateTime('-12 month'))->format('Y-m-d');
        $transactionDate = (new \DateTime('-6 months'))->format('Y-m-d');

        return <<<JSON
[
  {
    "record_type" : "tradeline",
    "external_resident_id" : "FAKE_RES_ID_{$this->fakeData->numberBetween()}",
    "first_name" : "{$this->fakeData->firstName()}",
    "last_name" : "{$this->fakeData->lastName()}",
    "birthdate" : "{$this->fakeData->date('Y-m-d', '2000-01-01')}",
    "ssn" : "{$this->fakeData->numerify('###-##-####')}",
    "email" : "{$this->fakeData->email()}",
    "mobile" : "{$this->fakeData->numerify('##########')}",
 
    "external_lease_id" : "FAKE_LEASE_ID_{$this->fakeData->numberBetween()}", 
    "address1" : "{$this->fakeData->streetAddress()}",
    "address2" : "{$this->fakeData->secondaryAddress()}",
    "city" : "{$this->fakeData->city()}",
    "state" : "{$this->fakeData->state()}",
    "zip" : "{$this->fakeData->postcode()}",
    "country" : "US", 
    "due_day" : {$this->fakeData->numberBetween(1, 31)},
    "start_at" : "{$startAt}",
    "rent" : "{$this->fakeData->randomFloat(2, 800, 2000)}",
 
    "external_transaction_id" : "FAKE_TR_ID_{$this->fakeData->numberBetween()}",
    "type" : "rent",
    "amount" : "{$this->fakeData->randomFloat(2, 800, 2000)}",
    "transaction_date" : "{$transactionDate}",
    "currency" : "usd"
   }
]
JSON;
    }

    protected function preparePaymentSourceDataRequest(string $paymentAccountType): PaymentSourceDataRequest
    {
        return PaymentSourceDataRequest::create(
            $this->preparePaymentAccount($paymentAccountType),
            $this->prepareAddress()
        );
    }

    /**
     * @throws \Exception
     */
    protected function preparePaymentAccount(string $paymentAccountType): PaymentAccount
    {
        switch ($paymentAccountType) {
            case PaymentAccountType::BANK:
                return BankAccount::create(
                    preg_replace('/[^ a-z0-9]/i', '', $this->fakeData->name()),
                    $this->fakeData->randomNumber(9, true),
                    $this->fakeData->randomElement(['044000037', '062202574']),
                    $this->fakeData->randomElement([
                        BankAccountType::CHECKING,
                        BankAccountType::SAVINGS,
                    ]),
                );
            case PaymentAccountType::CREDIT_CARD:
                return CreditCardAccount::create(
                    preg_replace('/[^ a-z0-9]/i', '', $this->fakeData->name()),
                    $this->fakeData->creditCardNumber(),
                    $this->fakeData->creditCardExpirationDate(),
                    $this->fakeData->randomNumber(3, true)
                );
            default:
                throw new \Exception('Unsupported payment account type');
        }
    }

    protected function prepareAddress(): PaymentAccountAddress
    {
        return PaymentAccountAddress::create(
            $this->fakeData->streetAddress(),
            $this->fakeData->city(),
            $this->fakeData->state(),
            $this->fakeData->postcode()
        );
    }

    protected function getTradelineClient(): TradelineClient
    {
        $client = TradelineClient::create($_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET']);
        if (!empty($_ENV['BASE_URL'])) {
            $client->setBaseUrl($_ENV['BASE_URL']);
        }

        return $client;
    }

    /**
     * Load credentials into the environment from .env file
     * and do some minor sanity checking
     */
    protected function loadEnvironment(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . "/../../");
        $dotenv->load();
        $dotenv->required(['CLIENT_ID', 'CLIENT_SECRET', 'PARTNER_USERNAME', 'PARTNER_PASSWORD'])->notEmpty();
    }
}
