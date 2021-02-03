<?php

namespace LevelCredit\Tradeline\Tests\Unit;

use LevelCredit\Tradeline\Enum\BankAccountType;
use LevelCredit\Tradeline\Exception\TradelineInvalidArgumentException;
use LevelCredit\Tradeline\Model\AuthenticateResponse;
use LevelCredit\Tradeline\Model\OrderResponse;
use LevelCredit\Tradeline\Model\PaymentSourceDataRequest;
use LevelCredit\Tradeline\Model\SubModel\BankAccount;
use LevelCredit\Tradeline\Model\SubModel\PaymentAccountAddress;
use LevelCredit\Tradeline\RequestHandler\RequestHandler;
use LevelCredit\Tradeline\Tests\Helper\WriteAttributeExtensionTrait;
use LevelCredit\Tradeline\TradelineClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TradelineClientTest extends TestCase
{
    use WriteAttributeExtensionTrait;

    /**
     * @test
     */
    public function shouldSetLoggerForRequestHandlerOnSetLogger(): void
    {
        $handlerMock = $this->createMock(RequestHandler::class);
        $handlerMock
            ->expects($this->once())
            ->method('setLogger')
            ->with($this->isInstanceOf(LoggerInterface::class))
            ->willReturn($handlerMock);

        $this->setRequestHandler(TradelineClient::create(), $handlerMock)
            ->setLogger($this->createMock(LoggerInterface::class));
    }

    /**
     * @test
     */
    public function shouldSetBaseUriForRequestHandlerOnSetBaseUrl(): void
    {
        $handlerMock = $this->createMock(RequestHandler::class);
        $handlerMock
            ->expects($this->once())
            ->method('setBaseUri')
            ->with('http://some.base.uri')
            ->willReturn($handlerMock);

        $this->setRequestHandler(TradelineClient::create(), $handlerMock)
            ->setBaseUrl('http://some.base.uri');
    }

    /**
     * @test
     */
    public function shouldGetPhpErrorOnPassIncorrectNumbersOfArgumentsOnAuthenticate(): void
    {
        $this->expectError();
        $this->expectErrorMessage(
            'Wrong number of arguments passed to LevelCredit\Tradeline\TradelineClient::authenticate'
        );

        $client = TradelineClient::create()->authenticate(
            'some_client_id',
            'some_client_secret',
            'some_username',
            'some_password',
            'some_refresh_token'
        );
    }

    /**
     * @test
     */
    public function shouldGetAccessTokenByClientIdClientSecretRefreshTokenOnAuthenticate(): void
    {
        $handlerMock = $this->createMock(RequestHandler::class);
        $handlerMock
            ->expects($this->once())
            ->method('getAccessTokenByRefreshToken')
            ->with(
                $this->equalTo('some_refresh_token'),
                $this->equalTo('client_id'),
                $this->equalTo('client_secret')
            )
            ->willReturn(
                $response = $this->createMock(AuthenticateResponse::class)
            );

        $this->assertSame(
            $response,
            $this->setRequestHandler(TradelineClient::create(), $handlerMock)
                ->authenticate('client_id', 'client_secret', 'some_refresh_token')
        );
    }

    /**
     * @test
     */
    public function shouldGetAccessTokenByRefreshTokenOnAuthenticate(): void
    {
        $handlerMock = $this->createMock(RequestHandler::class);
        $handlerMock
            ->expects($this->once())
            ->method('getAccessTokenByRefreshToken')
            ->with(
                $this->equalTo('some_refresh_token'),
                $this->equalTo(null),
                $this->equalTo(null)
            )
            ->willReturn(
                $response = $this->createMock(AuthenticateResponse::class)
            );

        $this->assertSame(
            $response,
            $this->setRequestHandler(TradelineClient::create(), $handlerMock)
                ->authenticate('some_refresh_token')
        );
    }

    /**
     * @test
     */
    public function shouldGetAccessTokenByClientIdClientSecretUsernamePasswordOnAuthenticate(): void
    {
        $handlerMock = $this->createMock(RequestHandler::class);
        $handlerMock
            ->expects($this->once())
            ->method('getAccessTokenByUsernamePassword')
            ->with(
                $this->equalTo('some_username'),
                $this->equalTo('some_password'),
                $this->equalTo('client_id'),
                $this->equalTo('client_secret')
            )
            ->willReturn(
                $response = $this->createMock(AuthenticateResponse::class)
            );

        $this->assertSame(
            $response,
            $this->setRequestHandler(TradelineClient::create(), $handlerMock)
                ->authenticate('client_id', 'client_secret', 'some_username', 'some_password')
        );
    }

    /**
     * @test
     */
    public function shouldGetAccessTokenByUsernamePasswordOnAuthenticate(): void
    {
        $handlerMock = $this->createMock(RequestHandler::class);
        $handlerMock
            ->expects($this->once())
            ->method('getAccessTokenByUsernamePassword')
            ->with(
                $this->equalTo('some_username'),
                $this->equalTo('some_password'),
                $this->equalTo(null),
                $this->equalTo(null)
            )
            ->willReturn(
                $response = $this->createMock(AuthenticateResponse::class)
            );

        $this->assertSame(
            $response,
            $this->setRequestHandler(TradelineClient::create(), $handlerMock)
                ->authenticate('some_username', 'some_password')
        );
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfSyncDataWithoutEmailOnPurchaseBackreporting(): void
    {
        $this->expectException(TradelineInvalidArgumentException::class);
        $this->expectExceptionMessage('Email should be present in sync data.');

        $handlerMock = $this->createMock(RequestHandler::class);

        $this->setRequestHandler(TradelineClient::create(), $handlerMock)
            ->purchaseBackreporting(
                'some_access_token',
                '{"some":"data"}',
                PaymentSourceDataRequest::create(
                    BankAccount::create('holderName', 123678, '0557758', BankAccountType::BUSINESS_CHECKING),
                    PaymentAccountAddress::create('123 Test str.', 'Test City', 'TS', '99999')
                )
            );
    }

    /**
     * @test
     */
    public function shouldReturnOrderResponseOnPurchaseBackreporting(): void
    {
        $paymentSourceData = PaymentSourceDataRequest::create(
            BankAccount::create('holderName', 123678, '0557758', BankAccountType::BUSINESS_CHECKING),
            PaymentAccountAddress::create('123 Test str.', 'Test City', 'TS', '99999')
        );

        $handlerMock = $this->createMock(RequestHandler::class);
        $handlerMock
            ->expects($this->once())
            ->method('setAccessToken')
            ->with($this->equalTo('some_access_token'))
            ->willReturn($handlerMock);
        $handlerMock
            ->expects($this->once())
            ->method('createTradelineSync')
            ->willReturn(123);
        $handlerMock
            ->expects($this->once())
            ->method('addDataToTradelineSync')
            ->with(
                $this->equalTo(123),
                $this->equalTo('{"some":"data", "email":"email@email.com"}')
            );
        $handlerMock
            ->expects($this->once())
            ->method('startTradelineSync')
            ->with($this->equalTo(123));
        $handlerMock
            ->expects($this->once())
            ->method('getSubscriptionResourceUrlByUserEmail')
            ->with($this->equalTo('email@email.com'))
            ->willReturn('http://some.url/resource/1');
        $handlerMock
            ->expects($this->once())
            ->method('payProduct')
            ->with(
                $this->equalTo('LC-BACKREPORT'),
                $this->equalTo(49.95),
                $this->equalTo('http://some.url/resource/1'),
                $this->equalTo($paymentSourceData)
            )
            ->willReturn($orderResponseMock = $this->createMock(OrderResponse::class));

        $this->assertSame(
            $orderResponseMock,
            $this->setRequestHandler(TradelineClient::create(), $handlerMock)
                ->purchaseBackreporting(
                    'some_access_token',
                    '{"some":"data", "email":"email@email.com"}',
                    $paymentSourceData
                )
        );
    }

    /**
     * @param TradelineClient $client
     * @param RequestHandler $handler
     * @return TradelineClient
     * @throws \ReflectionException
     */
    protected function setRequestHandler(TradelineClient $client, RequestHandler $handler): TradelineClient
    {
        $this->writeAttribute($client, 'requestHandler', $handler);

        return $client;
    }
}
