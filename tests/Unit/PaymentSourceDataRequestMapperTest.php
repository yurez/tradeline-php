<?php

namespace LevelCredit\Tradeline\Tests\Unit;

use LevelCredit\Tradeline\Enum\BankAccountType;
use LevelCredit\Tradeline\Enum\PaymentAccountType;
use LevelCredit\Tradeline\Mapping\PaymentSourceDataRequestMapper;
use LevelCredit\Tradeline\Model\PaymentSourceDataRequest;
use LevelCredit\Tradeline\Model\SubModel\BankAccount;
use LevelCredit\Tradeline\Model\SubModel\CreditCardAccount;
use LevelCredit\Tradeline\Model\SubModel\DebitCardAccount;
use LevelCredit\Tradeline\Model\SubModel\PaymentAccount;
use LevelCredit\Tradeline\Model\SubModel\PaymentAccountAddress;
use LevelCredit\LevelCreditApi\Enum\PaymentAccountType as ApiPaymentAccountType;
use LevelCredit\LevelCreditApi\Enum\BankAccountType as ApiBankAccountType;
use PHPUnit\Framework\TestCase;

class PaymentSourceDataRequestMapperTest extends TestCase
{
    /**
     * @test
     */
    public function shouldMapUnsupportedPaymentSourceDataToApiPaymentSource(): void
    {
        $paymentAccountMock = $this->createMock(PaymentAccount::class);
        $paymentAccountMock
            ->expects($this->once())
            ->method('getHolderName')
            ->willReturn('Some H. Name');

        $addressMock = $this->createMock(PaymentAccountAddress::class);

        $paymentSourceDataMock = $this->createMock(PaymentSourceDataRequest::class);
        $paymentSourceDataMock
            ->expects($this->any())
            ->method('getPaymentAccount')
            ->willReturn($paymentAccountMock);
        $paymentSourceDataMock
            ->expects($this->any())
            ->method('getAddress')
            ->willReturn($addressMock);

        $result = (array)PaymentSourceDataRequestMapper::map($paymentSourceDataMock);

        $this->assertEmpty($result["\0*\0type"]);
        $this->assertEquals('Some H. Name', $result["\0*\0name"]);
        $this->assertNull($result["\0*\0bank"]);
        $this->assertNull($result["\0*\0debitCard"]);
        $this->assertNull($result["\0*\0card"]);
    }

    /**
     * @test
     */
    public function shouldMapCreditCardPaymentSourceDataToApiPaymentSource(): void
    {
        $paymentAccountMock = $this->createMock(CreditCardAccount::class);
        $paymentAccountMock
            ->expects($this->once())
            ->method('getHolderName')
            ->willReturn('Some H. Name');
        $paymentAccountMock
            ->expects($this->exactly(2))
            ->method('getType')
            ->willReturn(PaymentAccountType::CREDIT_CARD);
        $paymentAccountMock
            ->expects($this->once())
            ->method('getAccountNumber')
            ->willReturn('4343-4343-4343-4343');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getSecurityCode')
            ->willReturn('444');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getExpirationDate')
            ->willReturn($expirationDate = new \DateTime('2030-12-31'));

        $addressMock = $this->createMock(PaymentAccountAddress::class);
        $addressMock
            ->expects($this->once())
            ->method('getStreet')
            ->willReturn('123 Street');
        $addressMock
            ->expects($this->once())
            ->method('getCity')
            ->willReturn('Test City');
        $addressMock
            ->expects($this->once())
            ->method('getState')
            ->willReturn('TS');
        $addressMock
            ->expects($this->once())
            ->method('getZip')
            ->willReturn('99999');

        $paymentSourceDataMock = $this->createMock(PaymentSourceDataRequest::class);
        $paymentSourceDataMock
            ->expects($this->any())
            ->method('getPaymentAccount')
            ->willReturn($paymentAccountMock);
        $paymentSourceDataMock
            ->expects($this->any())
            ->method('getAddress')
            ->willReturn($addressMock);

        $result = (array)PaymentSourceDataRequestMapper::map($paymentSourceDataMock);

        $this->assertEquals(ApiPaymentAccountType::CARD, $result["\0*\0type"]);
        $this->assertEquals('Some H. Name', $result["\0*\0name"]);
        $this->assertNull($result["\0*\0bank"]);
        $this->assertNull($result["\0*\0debitCard"]);

        $address = (array)$result["\0*\0address"];

        $this->assertEquals('123 Street', $address["\0*\0street"]);
        $this->assertEquals('Test City', $address["\0*\0city"]);
        $this->assertEquals('TS', $address["\0*\0state"]);
        $this->assertEquals('99999', $address["\0*\0zip"]);

        $cardAccount = (array)$result["\0*\0card"];

        $this->assertEquals('4343434343434343', $cardAccount["\0*\0account"]);
        $this->assertEquals('444', $cardAccount["\0*\0cvv"]);
        $this->assertEquals('2030-12', $cardAccount["\0*\0expiration"]);
    }

    /**
     * @test
     */
    public function shouldMapDebitCardPaymentSourceDataToApiPaymentSource(): void
    {
        $paymentAccountMock = $this->createMock(DebitCardAccount::class);
        $paymentAccountMock
            ->expects($this->once())
            ->method('getHolderName')
            ->willReturn('Some H. Name');
        $paymentAccountMock
            ->expects($this->exactly(2))
            ->method('getType')
            ->willReturn(PaymentAccountType::DEBIT_CARD);
        $paymentAccountMock
            ->expects($this->once())
            ->method('getAccountNumber')
            ->willReturn('4343-4343-4343-4343');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getSecurityCode')
            ->willReturn('444');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getExpirationDate')
            ->willReturn($expirationDate = new \DateTime('2030-12-31'));

        $addressMock = $this->createMock(PaymentAccountAddress::class);
        $addressMock
            ->expects($this->once())
            ->method('getStreet')
            ->willReturn('123 Street');
        $addressMock
            ->expects($this->once())
            ->method('getCity')
            ->willReturn('Test City');
        $addressMock
            ->expects($this->once())
            ->method('getState')
            ->willReturn('TS');
        $addressMock
            ->expects($this->once())
            ->method('getZip')
            ->willReturn('99999');

        $paymentSourceDataMock = $this->createMock(PaymentSourceDataRequest::class);
        $paymentSourceDataMock
            ->expects($this->any())
            ->method('getPaymentAccount')
            ->willReturn($paymentAccountMock);
        $paymentSourceDataMock
            ->expects($this->any())
            ->method('getAddress')
            ->willReturn($addressMock);

        $result = (array)PaymentSourceDataRequestMapper::map($paymentSourceDataMock);

        $this->assertEquals(ApiPaymentAccountType::DEBIT_CARD, $result["\0*\0type"]);
        $this->assertEquals('Some H. Name', $result["\0*\0name"]);
        $this->assertNull($result["\0*\0bank"]);
        $this->assertNull($result["\0*\0card"]);

        $address = (array)$result["\0*\0address"];

        $this->assertEquals('123 Street', $address["\0*\0street"]);
        $this->assertEquals('Test City', $address["\0*\0city"]);
        $this->assertEquals('TS', $address["\0*\0state"]);
        $this->assertEquals('99999', $address["\0*\0zip"]);

        $debitCardAccount = (array)$result["\0*\0debitCard"];

        $this->assertEquals('4343434343434343', $debitCardAccount["\0*\0account"]);
        $this->assertEquals('444', $debitCardAccount["\0*\0cvv"]);
        $this->assertEquals('2030-12', $debitCardAccount["\0*\0expiration"]);
    }

    /**
     * @test
     */
    public function shouldMapSavingsBankPaymentSourceDataToApiPaymentSource(): void
    {
        $paymentAccountMock = $this->createMock(BankAccount::class);
        $paymentAccountMock
            ->expects($this->once())
            ->method('getHolderName')
            ->willReturn('Some H. Name');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getType')
            ->willReturn(PaymentAccountType::BANK);
        $paymentAccountMock
            ->expects($this->once())
            ->method('getAccountNumber')
            ->willReturn('64376437-4343-78');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getRoutingNumber')
            ->willReturn('07888983');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getBankAccountType')
            ->willReturn(BankAccountType::SAVINGS);

        $addressMock = $this->createMock(PaymentAccountAddress::class);
        $addressMock
            ->expects($this->once())
            ->method('getStreet')
            ->willReturn('123 Street');
        $addressMock
            ->expects($this->once())
            ->method('getCity')
            ->willReturn('Test City');
        $addressMock
            ->expects($this->once())
            ->method('getState')
            ->willReturn('TS');
        $addressMock
            ->expects($this->once())
            ->method('getZip')
            ->willReturn('99999');

        $paymentSourceDataMock = $this->createMock(PaymentSourceDataRequest::class);
        $paymentSourceDataMock
            ->expects($this->any())
            ->method('getPaymentAccount')
            ->willReturn($paymentAccountMock);
        $paymentSourceDataMock
            ->expects($this->any())
            ->method('getAddress')
            ->willReturn($addressMock);

        $result = (array)PaymentSourceDataRequestMapper::map($paymentSourceDataMock);

        $this->assertEquals(ApiPaymentAccountType::BANK, $result["\0*\0type"]);
        $this->assertEquals('Some H. Name', $result["\0*\0name"]);
        $this->assertNull($result["\0*\0debitCard"]);
        $this->assertNull($result["\0*\0card"]);

        $address = (array)$result["\0*\0address"];

        $this->assertEquals('123 Street', $address["\0*\0street"]);
        $this->assertEquals('Test City', $address["\0*\0city"]);
        $this->assertEquals('TS', $address["\0*\0state"]);
        $this->assertEquals('99999', $address["\0*\0zip"]);

        $bankAccount = (array)$result["\0*\0bank"];

        $this->assertEquals('64376437434378', $bankAccount["\0*\0account"]);
        $this->assertEquals('07888983', $bankAccount["\0*\0routing"]);
        $this->assertEquals(ApiBankAccountType::SAVINGS, $bankAccount["\0*\0type"]);
    }

    /**
     * @test
     */
    public function shouldMapCheckingBankPaymentSourceDataToApiPaymentSource(): void
    {
        $paymentAccountMock = $this->createMock(BankAccount::class);
        $paymentAccountMock
            ->expects($this->once())
            ->method('getHolderName')
            ->willReturn('Some H. Name');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getType')
            ->willReturn(PaymentAccountType::BANK);
        $paymentAccountMock
            ->expects($this->once())
            ->method('getAccountNumber')
            ->willReturn('64376437-4343-78');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getRoutingNumber')
            ->willReturn('07888983');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getBankAccountType')
            ->willReturn(BankAccountType::CHECKING);

        $addressMock = $this->createMock(PaymentAccountAddress::class);
        $addressMock
            ->expects($this->once())
            ->method('getStreet')
            ->willReturn('123 Street');
        $addressMock
            ->expects($this->once())
            ->method('getCity')
            ->willReturn('Test City');
        $addressMock
            ->expects($this->once())
            ->method('getState')
            ->willReturn('TS');
        $addressMock
            ->expects($this->once())
            ->method('getZip')
            ->willReturn('99999');

        $paymentSourceDataMock = $this->createMock(PaymentSourceDataRequest::class);
        $paymentSourceDataMock
            ->expects($this->any())
            ->method('getPaymentAccount')
            ->willReturn($paymentAccountMock);
        $paymentSourceDataMock
            ->expects($this->any())
            ->method('getAddress')
            ->willReturn($addressMock);

        $result = (array)PaymentSourceDataRequestMapper::map($paymentSourceDataMock);

        $this->assertEquals(ApiPaymentAccountType::BANK, $result["\0*\0type"]);
        $this->assertEquals('Some H. Name', $result["\0*\0name"]);
        $this->assertNull($result["\0*\0debitCard"]);
        $this->assertNull($result["\0*\0card"]);

        $address = (array)$result["\0*\0address"];

        $this->assertEquals('123 Street', $address["\0*\0street"]);
        $this->assertEquals('Test City', $address["\0*\0city"]);
        $this->assertEquals('TS', $address["\0*\0state"]);
        $this->assertEquals('99999', $address["\0*\0zip"]);

        $bankAccount = (array)$result["\0*\0bank"];

        $this->assertEquals('64376437434378', $bankAccount["\0*\0account"]);
        $this->assertEquals('07888983', $bankAccount["\0*\0routing"]);
        $this->assertEquals(ApiBankAccountType::CHECKING, $bankAccount["\0*\0type"]);
    }

    /**
     * @test
     */
    public function shouldMapBusinessCheckingBankPaymentSourceDataToApiPaymentSource(): void
    {
        $paymentAccountMock = $this->createMock(BankAccount::class);
        $paymentAccountMock
            ->expects($this->once())
            ->method('getHolderName')
            ->willReturn('Some H. Name');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getType')
            ->willReturn(PaymentAccountType::BANK);
        $paymentAccountMock
            ->expects($this->once())
            ->method('getAccountNumber')
            ->willReturn('64376437-4343-78');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getRoutingNumber')
            ->willReturn('07888983');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getBankAccountType')
            ->willReturn(BankAccountType::BUSINESS_CHECKING);

        $addressMock = $this->createMock(PaymentAccountAddress::class);
        $addressMock
            ->expects($this->once())
            ->method('getStreet')
            ->willReturn('123 Street');
        $addressMock
            ->expects($this->once())
            ->method('getCity')
            ->willReturn('Test City');
        $addressMock
            ->expects($this->once())
            ->method('getState')
            ->willReturn('TS');
        $addressMock
            ->expects($this->once())
            ->method('getZip')
            ->willReturn('99999');

        $paymentSourceDataMock = $this->createMock(PaymentSourceDataRequest::class);
        $paymentSourceDataMock
            ->expects($this->any())
            ->method('getPaymentAccount')
            ->willReturn($paymentAccountMock);
        $paymentSourceDataMock
            ->expects($this->any())
            ->method('getAddress')
            ->willReturn($addressMock);

        $result = (array)PaymentSourceDataRequestMapper::map($paymentSourceDataMock);

        $this->assertEquals(ApiPaymentAccountType::BANK, $result["\0*\0type"]);
        $this->assertEquals('Some H. Name', $result["\0*\0name"]);
        $this->assertNull($result["\0*\0debitCard"]);
        $this->assertNull($result["\0*\0card"]);

        $address = (array)$result["\0*\0address"];

        $this->assertEquals('123 Street', $address["\0*\0street"]);
        $this->assertEquals('Test City', $address["\0*\0city"]);
        $this->assertEquals('TS', $address["\0*\0state"]);
        $this->assertEquals('99999', $address["\0*\0zip"]);

        $bankAccount = (array)$result["\0*\0bank"];

        $this->assertEquals('64376437434378', $bankAccount["\0*\0account"]);
        $this->assertEquals('07888983', $bankAccount["\0*\0routing"]);
        $this->assertEquals(ApiBankAccountType::BUSINESS_CHECKING, $bankAccount["\0*\0type"]);
    }

    /**
     * @test
     */
    public function shouldMapUnknownBankPaymentSourceDataToApiPaymentSource(): void
    {
        $paymentAccountMock = $this->createMock(BankAccount::class);
        $paymentAccountMock
            ->expects($this->once())
            ->method('getHolderName')
            ->willReturn('Some H. Name');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getType')
            ->willReturn(PaymentAccountType::BANK);
        $paymentAccountMock
            ->expects($this->once())
            ->method('getAccountNumber')
            ->willReturn('64376437-4343-78');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getRoutingNumber')
            ->willReturn('07888983');
        $paymentAccountMock
            ->expects($this->once())
            ->method('getBankAccountType')
            ->willReturn('unknown');

        $addressMock = $this->createMock(PaymentAccountAddress::class);
        $addressMock
            ->expects($this->once())
            ->method('getStreet')
            ->willReturn('123 Street');
        $addressMock
            ->expects($this->once())
            ->method('getCity')
            ->willReturn('Test City');
        $addressMock
            ->expects($this->once())
            ->method('getState')
            ->willReturn('TS');
        $addressMock
            ->expects($this->once())
            ->method('getZip')
            ->willReturn('99999');

        $paymentSourceDataMock = $this->createMock(PaymentSourceDataRequest::class);
        $paymentSourceDataMock
            ->expects($this->any())
            ->method('getPaymentAccount')
            ->willReturn($paymentAccountMock);
        $paymentSourceDataMock
            ->expects($this->any())
            ->method('getAddress')
            ->willReturn($addressMock);

        $result = (array)PaymentSourceDataRequestMapper::map($paymentSourceDataMock);

        $this->assertEquals(ApiPaymentAccountType::BANK, $result["\0*\0type"]);
        $this->assertEquals('Some H. Name', $result["\0*\0name"]);
        $this->assertNull($result["\0*\0debitCard"]);
        $this->assertNull($result["\0*\0card"]);

        $address = (array)$result["\0*\0address"];

        $this->assertEquals('123 Street', $address["\0*\0street"]);
        $this->assertEquals('Test City', $address["\0*\0city"]);
        $this->assertEquals('TS', $address["\0*\0state"]);
        $this->assertEquals('99999', $address["\0*\0zip"]);

        $bankAccount = (array)$result["\0*\0bank"];

        $this->assertEquals('64376437434378', $bankAccount["\0*\0account"]);
        $this->assertEquals('07888983', $bankAccount["\0*\0routing"]);
        $this->assertEmpty($bankAccount["\0*\0type"]);
    }
}
