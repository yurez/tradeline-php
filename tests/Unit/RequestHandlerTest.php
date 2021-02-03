<?php

namespace LevelCredit\Tradeline\Tests\Unit;

use Doctrine\Common\Collections\ArrayCollection;
use LevelCredit\LevelCreditApi\Enum\TradelineSyncType;
use LevelCredit\LevelCreditApi\Enum\UserEmbeddedEntities;
use LevelCredit\LevelCreditApi\Exception\ClientException;
use LevelCredit\LevelCreditApi\LevelCreditApiClient;
use LevelCredit\LevelCreditApi\Logging\DefaultLogHandler;
use LevelCredit\LevelCreditApi\Model\Request\CreateTradelineSyncRequest;
use LevelCredit\LevelCreditApi\Model\Request\GetPartnerUsersFilter;
use LevelCredit\LevelCreditApi\Model\Request\PatchTradelineSyncRequest;
use LevelCredit\LevelCreditApi\Model\Request\PayProductRequest;
use LevelCredit\LevelCreditApi\Model\Response\AccessTokenResponse;
use LevelCredit\LevelCreditApi\Model\Response\EmptyResponse;
use LevelCredit\LevelCreditApi\Model\Response\ErrorCollection;
use LevelCredit\LevelCreditApi\Model\Response\OrderResourceResponse;
use LevelCredit\LevelCreditApi\Model\Response\Resource\AccessToken;
use LevelCredit\LevelCreditApi\Model\Response\Resource\Order;
use LevelCredit\LevelCreditApi\Model\Response\Resource\Subscription;
use LevelCredit\LevelCreditApi\Model\Response\Resource\Sync;
use LevelCredit\LevelCreditApi\Model\Response\Resource\User;
use LevelCredit\LevelCreditApi\Model\Response\SyncResourceResponse;
use LevelCredit\LevelCreditApi\Model\Response\UserCollectionResponse;
use LevelCredit\Tradeline\Enum\BankAccountType;
use LevelCredit\Tradeline\Enum\OrderStatus;
use LevelCredit\Tradeline\Exception\TradelineClientException;
use LevelCredit\Tradeline\Exception\TradelineLogicException;
use LevelCredit\Tradeline\Exception\TradelineResponseException;
use LevelCredit\Tradeline\Mapping\PaymentSourceDataRequestMapper;
use LevelCredit\Tradeline\Model\AuthenticateResponse;
use LevelCredit\Tradeline\Model\OrderResponse;
use LevelCredit\Tradeline\Model\PaymentSourceDataRequest;
use LevelCredit\Tradeline\Model\SubModel\BankAccount;
use LevelCredit\Tradeline\Model\SubModel\CreditCardAccount;
use LevelCredit\Tradeline\Model\SubModel\DebitCardAccount;
use LevelCredit\Tradeline\Model\SubModel\PaymentAccountAddress;
use LevelCredit\Tradeline\RequestHandler\RequestHandler;
use LevelCredit\Tradeline\Tests\Helper\WriteAttributeExtensionTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RequestHandlerTest extends TestCase
{
    use WriteAttributeExtensionTrait;

    /**
     * @test
     */
    public function shouldResetDefaultLogHandlerOnSetLogger(): void
    {
        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('disableLogHandlers')
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->once())
            ->method('addLogHandler')
            ->with($this->isInstanceOf(DefaultLogHandler::class))
            ->willReturn($apiClientMock);

        $this
            ->setApiClient(new RequestHandler(), $apiClientMock)
            ->setLogger($this->createMock(LoggerInterface::class));
    }

    /**
     * @test
     */
    public function shouldSetBaseUriForAPiClientOnSetBaseUri(): void
    {
        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('setBaseUri')
            ->with($this->equalTo('http//new.base.uri'))
            ->willReturn($apiClientMock);

        $this
            ->setApiClient(new RequestHandler(), $apiClientMock)
            ->setBaseUri('http//new.base.uri');
    }

    /**
     * @test
     */
    public function shouldSetAccessTokenForAPiClientOnSetAccessToken(): void
    {
        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('setAccessToken')
            ->with($this->equalTo('some_access_token'))
            ->willReturn($apiClientMock);

        $this
            ->setApiClient(new RequestHandler(), $apiClientMock)
            ->setAccessToken('some_access_token');
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenGetExceptionFromApiClientOnAuthenticateByUsernamePassword(): void
    {
        $this->expectException(TradelineClientException::class);
        $this->expectErrorMessage('Get error on authenticate request: Some error on http request');

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->exactly(2))
            ->method('setClientId')
            ->withConsecutive(
                [$this->equalTo('client_id')],
                [$this->equalTo('main_client_id')]
            )
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->exactly(2))
            ->method('setClientSecret')
            ->withConsecutive(
                [$this->equalTo('client_secret')],
                [$this->equalTo('main_client_secret')]
            )
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->once())
            ->method('getAccessTokenByUsernamePassword')
            ->with($this->equalTo('some_username'), $this->equalTo('some_password'))
            ->willThrowException(new ClientException('Some error on http request'));

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->getAccessTokenByUsernamePassword('some_username', 'some_password', 'client_id', 'client_secret');
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnFailedResponseByUsernamePassword(): void
    {
        $this->expectException(TradelineResponseException::class);
        $this->expectErrorMessage('The client credentials are invalid.');

        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->never())
            ->method('isEmpty');
        $errorCollectionMock
            ->expects($this->atLeastOnce())
            ->method('__toString')
            ->willReturn('The client credentials are invalid.');

        $apiResponseMock = $this->createMock(AccessTokenResponse::class);
        $apiResponseMock
            ->expects($this->never())
            ->method('getResource');
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(400);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->exactly(2))
            ->method('setClientId')
            ->withConsecutive(
                [$this->equalTo('client_id')],
                [$this->equalTo('main_client_id')]
            )
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->exactly(2))
            ->method('setClientSecret')
            ->withConsecutive(
                [$this->equalTo('client_secret')],
                [$this->equalTo('main_client_secret')]
            )
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->once())
            ->method('getAccessTokenByUsernamePassword')
            ->with($this->equalTo('some_username'), $this->equalTo('some_password'))
            ->willReturn($apiResponseMock);

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->getAccessTokenByUsernamePassword('some_username', 'some_password', 'client_id', 'client_secret');

    }

    /**
     * @test
     */
    public function shouldGetAccessTokenByClientIdClientSecretUsernamePassword(): void
    {
        $accessTokenMock = $this->createMock(AccessToken::class);
        $accessTokenMock
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn('some_access_token');
        $accessTokenMock
            ->expects($this->once())
            ->method('getRefreshToken')
            ->willReturn('some_refresh_token');
        $accessTokenMock
            ->expects($this->once())
            ->method('getExpiresIn')
            ->willReturn(3600);

        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->atLeastOnce())
            ->method('isEmpty')
            ->willReturn(true);

        $apiResponseMock = $this->createMock(AccessTokenResponse::class);
        $apiResponseMock
            ->expects($this->exactly(3))
            ->method('getResource')
            ->willReturn($accessTokenMock);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(200);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->exactly(2))
            ->method('setClientId')
            ->withConsecutive(
                [$this->equalTo('client_id')],
                [$this->equalTo('main_client_id')]
            )
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->exactly(2))
            ->method('setClientSecret')
            ->withConsecutive(
                [$this->equalTo('client_secret')],
                [$this->equalTo('main_client_secret')]
            )
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->once())
            ->method('getAccessTokenByUsernamePassword')
            ->with($this->equalTo('some_username'), $this->equalTo('some_password'))
            ->willReturn($apiResponseMock);

        $result = $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->getAccessTokenByUsernamePassword('some_username', 'some_password', 'client_id', 'client_secret');

        $this->assertInstanceOf(AuthenticateResponse::class, $result);
        $this->assertEquals('some_access_token', $result->getAccessToken());
        $this->assertEquals('some_refresh_token', $result->getRefreshToken());
        $this->assertEquals(3600, $result->getExpiresIn());
    }

    /**
     * @test
     */
    public function shouldGetAccessTokenByUsernamePassword(): void
    {
        $accessTokenMock = $this->createMock(AccessToken::class);
        $accessTokenMock
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn('some_access_token');
        $accessTokenMock
            ->expects($this->once())
            ->method('getRefreshToken')
            ->willReturn('some_refresh_token');
        $accessTokenMock
            ->expects($this->once())
            ->method('getExpiresIn')
            ->willReturn(3600);

        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->atLeastOnce())
            ->method('isEmpty')
            ->willReturn(true);

        $apiResponseMock = $this->createMock(AccessTokenResponse::class);
        $apiResponseMock
            ->expects($this->exactly(3))
            ->method('getResource')
            ->willReturn($accessTokenMock);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(200);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('setClientId')
            ->with($this->equalTo('main_client_id'));
        $apiClientMock
            ->expects($this->once())
            ->method('setClientSecret')
            ->with($this->equalTo('main_client_secret'));
        $apiClientMock
            ->expects($this->once())
            ->method('getAccessTokenByUsernamePassword')
            ->with($this->equalTo('some_username'), $this->equalTo('some_password'))
            ->willReturn($apiResponseMock);

        $result = $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->getAccessTokenByUsernamePassword('some_username', 'some_password');

        $this->assertInstanceOf(AuthenticateResponse::class, $result);
        $this->assertEquals('some_access_token', $result->getAccessToken());
        $this->assertEquals('some_refresh_token', $result->getRefreshToken());
        $this->assertEquals(3600, $result->getExpiresIn());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenGetExceptionFromApiClientOnAuthenticateByRefreshToken(): void
    {
        $this->expectException(TradelineClientException::class);
        $this->expectErrorMessage('Get error on authenticate request: Some error on http request');

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->exactly(2))
            ->method('setClientId')
            ->withConsecutive(
                [$this->equalTo('client_id')],
                [$this->equalTo('main_client_id')]
            )
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->exactly(2))
            ->method('setClientSecret')
            ->withConsecutive(
                [$this->equalTo('client_secret')],
                [$this->equalTo('main_client_secret')]
            )
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->once())
            ->method('getAccessTokenByRefreshToken')
            ->with($this->equalTo('some_refresh_token'))
            ->willThrowException(new ClientException('Some error on http request'));

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->getAccessTokenByRefreshToken('some_refresh_token', 'client_id', 'client_secret');
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnFailedResponseByRefreshToken(): void
    {
        $this->expectException(TradelineResponseException::class);
        $this->expectErrorMessage('The client credentials are invalid.');

        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->never())
            ->method('isEmpty');
        $errorCollectionMock
            ->expects($this->atLeastOnce())
            ->method('__toString')
            ->willReturn('The client credentials are invalid.');

        $apiResponseMock = $this->createMock(AccessTokenResponse::class);
        $apiResponseMock
            ->expects($this->never())
            ->method('getResource');
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(400);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->exactly(2))
            ->method('setClientId')
            ->withConsecutive(
                [$this->equalTo('client_id')],
                [$this->equalTo('main_client_id')]
            )
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->exactly(2))
            ->method('setClientSecret')
            ->withConsecutive(
                [$this->equalTo('client_secret')],
                [$this->equalTo('main_client_secret')]
            )
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->once())
            ->method('getAccessTokenByRefreshToken')
            ->with($this->equalTo('some_refresh_token'))
            ->willReturn($apiResponseMock);

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->getAccessTokenByRefreshToken('some_refresh_token', 'client_id', 'client_secret');
    }

    /**
     * @test
     */
    public function shouldGetAccessTokenByClientIdClientSecretRefreshToken(): void
    {
        $accessTokenMock = $this->createMock(AccessToken::class);
        $accessTokenMock
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn('some_access_token');
        $accessTokenMock
            ->expects($this->once())
            ->method('getRefreshToken')
            ->willReturn('some_refresh_token');
        $accessTokenMock
            ->expects($this->once())
            ->method('getExpiresIn')
            ->willReturn(3600);

        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->atLeastOnce())
            ->method('isEmpty')
            ->willReturn(true);

        $apiResponseMock = $this->createMock(AccessTokenResponse::class);
        $apiResponseMock
            ->expects($this->exactly(3))
            ->method('getResource')
            ->willReturn($accessTokenMock);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(200);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->exactly(2))
            ->method('setClientId')
            ->withConsecutive(
                [$this->equalTo('client_id')],
                [$this->equalTo('main_client_id')]
            )
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->exactly(2))
            ->method('setClientSecret')
            ->withConsecutive(
                [$this->equalTo('client_secret')],
                [$this->equalTo('main_client_secret')]
            )
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->once())
            ->method('getAccessTokenByRefreshToken')
            ->with($this->equalTo('some_refresh_token'))
            ->willReturn($apiResponseMock);

        $result = $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->getAccessTokenByRefreshToken('some_refresh_token', 'client_id', 'client_secret');

        $this->assertInstanceOf(AuthenticateResponse::class, $result);
        $this->assertEquals('some_access_token', $result->getAccessToken());
        $this->assertEquals('some_refresh_token', $result->getRefreshToken());
        $this->assertEquals(3600, $result->getExpiresIn());
    }

    /**
     * @test
     */
    public function shouldGetAccessTokenByRefreshToken(): void
    {
        $accessTokenMock = $this->createMock(AccessToken::class);
        $accessTokenMock
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn('some_access_token');
        $accessTokenMock
            ->expects($this->once())
            ->method('getRefreshToken')
            ->willReturn('some_refresh_token');
        $accessTokenMock
            ->expects($this->once())
            ->method('getExpiresIn')
            ->willReturn(3600);

        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->atLeastOnce())
            ->method('isEmpty')
            ->willReturn(true);

        $apiResponseMock = $this->createMock(AccessTokenResponse::class);
        $apiResponseMock
            ->expects($this->exactly(3))
            ->method('getResource')
            ->willReturn($accessTokenMock);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(200);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('setClientId')
            ->with($this->equalTo('main_client_id'));
        $apiClientMock
            ->expects($this->once())
            ->method('setClientSecret')
            ->with($this->equalTo('main_client_secret'));
        $apiClientMock
            ->expects($this->once())
            ->method('getAccessTokenByRefreshToken')
            ->with($this->equalTo('some_refresh_token'))
            ->willReturn($apiResponseMock);

        $result = $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->getAccessTokenByRefreshToken('some_refresh_token');

        $this->assertInstanceOf(AuthenticateResponse::class, $result);
        $this->assertEquals('some_access_token', $result->getAccessToken());
        $this->assertEquals('some_refresh_token', $result->getRefreshToken());
        $this->assertEquals(3600, $result->getExpiresIn());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenGetExceptionFromApiClientOnCreateTradelineSync(): void
    {
        $this->expectException(TradelineClientException::class);
        $this->expectErrorMessage('Get error on create tradeline sync request: Some error on http request');

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('createTradelineSync')
            ->with(
                $this->equalTo(CreateTradelineSyncRequest::create()->setType(TradelineSyncType::SYNCHRONOUS)),
                $this->equalTo(null)
            )
            ->willThrowException(new ClientException('Some error on http request'));

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->createTradelineSync();
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenFailedResponseOnCreateTradelineSync(): void
    {
        $this->expectException(TradelineResponseException::class);
        $this->expectErrorMessage('The client credentials are invalid.');

        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->never())
            ->method('isEmpty');
        $errorCollectionMock
            ->expects($this->atLeastOnce())
            ->method('__toString')
            ->willReturn('The client credentials are invalid.');

        $apiResponseMock = $this->createMock(SyncResourceResponse::class);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(401);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('createTradelineSync')
            ->with(
                $this->equalTo(CreateTradelineSyncRequest::create()->setType(TradelineSyncType::SYNCHRONOUS)),
                $this->equalTo('invalid_access_token')
            )
            ->willReturn($apiResponseMock);

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->createTradelineSync('invalid_access_token');
    }

    /**
     * @test
     */
    public function shouldReturnSyncIdOnOnCreateTradelineSync(): void
    {
        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->once())
            ->method('isEmpty')
            ->willReturn(true);
        $errorCollectionMock
            ->expects($this->never())
            ->method('__toString');

        $syncResourceMock = $this->createMock(Sync::class);
        $syncResourceMock
            ->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(123);

        $apiResponseMock = $this->createMock(SyncResourceResponse::class);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(201);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getResource')
            ->willReturn($syncResourceMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('createTradelineSync')
            ->with(
                $this->equalTo(CreateTradelineSyncRequest::create()->setType(TradelineSyncType::SYNCHRONOUS)),
                $this->equalTo(null)
            )
            ->willReturn($apiResponseMock);

        $this->assertEquals(
            123,
            $this
                ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
                ->createTradelineSync()
        );
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenGetExceptionFromApiClientOnAddDataToTradelineSync(): void
    {
        $this->expectException(TradelineClientException::class);
        $this->expectErrorMessage('Get error on add data to tradeline sync request: Some error on http request');

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('addDataToTradelineSync')
            ->with(
                $this->equalTo(123),
                $this->equalTo('{"some": "data"}'),
                $this->equalTo(null)
            )
            ->willThrowException(new ClientException('Some error on http request'));

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->addDataToTradelineSync(123, '{"some": "data"}');
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenFailedResponseOnAddDataToTradelineSync(): void
    {
        $this->expectException(TradelineResponseException::class);
        $this->expectErrorMessage('Sync not found.');

        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->never())
            ->method('isEmpty');
        $errorCollectionMock
            ->expects($this->atLeastOnce())
            ->method('__toString')
            ->willReturn('Sync not found.');

        $apiResponseMock = $this->createMock(EmptyResponse::class);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(404);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('addDataToTradelineSync')
            ->with(
                $this->equalTo(123),
                $this->equalTo('{"some": "data"}'),
                $this->equalTo('valid_access_token')
            )
            ->willReturn($apiResponseMock);

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->addDataToTradelineSync(123, '{"some": "data"}', 'valid_access_token');
    }

    /**
     * @test
     */
    public function shouldSuccessAddDataToSyncOnAddDataToTradelineSync(): void
    {
        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->once())
            ->method('isEmpty')
            ->willReturn(true);
        $errorCollectionMock
            ->expects($this->never())
            ->method('__toString');

        $apiResponseMock = $this->createMock(EmptyResponse::class);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(204);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('addDataToTradelineSync')
            ->with(
                $this->equalTo(123),
                $this->equalTo('{"some": "data"}'),
                $this->equalTo(null)
            )
            ->willReturn($apiResponseMock);

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->addDataToTradelineSync(123, '{"some": "data"}');
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenGetExceptionFromApiClientOnStartTradelineSync(): void
    {
        $this->expectException(TradelineClientException::class);
        $this->expectErrorMessage('Get error on start tradeline sync request: Some error on http request');

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('patchTradelineSync')
            ->with(
                $this->equalTo(123),
                $this->equalTo(PatchTradelineSyncRequest::create()),
                $this->equalTo(null)
            )
            ->willThrowException(new ClientException('Some error on http request'));

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->startTradelineSync(123);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenFailedResponseOnStartTradelineSync(): void
    {
        $this->expectException(TradelineResponseException::class);
        $this->expectErrorMessage('Import type is invalid.');

        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->never())
            ->method('isEmpty');
        $errorCollectionMock
            ->expects($this->atLeastOnce())
            ->method('__toString')
            ->willReturn('Import type is invalid.');

        $apiResponseMock = $this->createMock(EmptyResponse::class);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(403);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('patchTradelineSync')
            ->with(
                $this->equalTo(123),
                $this->equalTo(PatchTradelineSyncRequest::create()),
                $this->equalTo('valid_access_token')
            )
            ->willReturn($apiResponseMock);

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->startTradelineSync(123, 'valid_access_token');
    }

    /**
     * @test
     */
    public function shouldSuccessStartImportOnStartTradelineSync(): void
    {
        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->once())
            ->method('isEmpty')
            ->willReturn(true);
        $errorCollectionMock
            ->expects($this->never())
            ->method('__toString');

        $apiResponseMock = $this->createMock(EmptyResponse::class);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(204);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('patchTradelineSync')
            ->with(
                $this->equalTo(123),
                $this->equalTo(PatchTradelineSyncRequest::create()),
                $this->equalTo(null)
            )
            ->willReturn($apiResponseMock);

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->startTradelineSync(123);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenGetExceptionFromApiClientOnGetSubscriptionResourceUrlByUserEmail(): void
    {
        $this->expectException(TradelineClientException::class);
        $this->expectErrorMessage('Get error on get subscription url request: Some error on http request');

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('getPartnerUsers')
            ->with(
                $this->equalTo(
                    GetPartnerUsersFilter::create()
                        ->setEmail('email@email.com')
                        ->addEmbedded(UserEmbeddedEntities::SUBSCRIPTIONS)
                ),
                $this->equalTo(null)
            )
            ->willThrowException(new ClientException('Some error on http request'));

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->getSubscriptionResourceUrlByUserEmail('email@email.com');
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenFailedResponseOnGetSubscriptionResourceUrlByUserEmail(): void
    {
        $this->expectException(TradelineResponseException::class);
        $this->expectErrorMessage('Email is invalid.');

        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->never())
            ->method('isEmpty');
        $errorCollectionMock
            ->expects($this->atLeastOnce())
            ->method('__toString')
            ->willReturn('Email is invalid.');

        $apiResponseMock = $this->createMock(UserCollectionResponse::class);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(400);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('getPartnerUsers')
            ->with(
                GetPartnerUsersFilter::create()
                    ->setEmail('invalid#email')
                    ->addEmbedded(UserEmbeddedEntities::SUBSCRIPTIONS),
                $this->equalTo('valid_access_token')
            )
            ->willReturn($apiResponseMock);

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->getSubscriptionResourceUrlByUserEmail('invalid#email', 'valid_access_token');
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfUserNotFoundByEmailOnGetSubscriptionResourceUrlByUserEmail(): void
    {
        $this->expectException(TradelineLogicException::class);
        $this->expectErrorMessage('User was not imported correct.');

        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->once())
            ->method('isEmpty')
            ->willReturn(true);
        $errorCollectionMock
            ->expects($this->never())
            ->method('__toString');

        $elementCollectionMock = $this->createMock(ArrayCollection::class);
        $elementCollectionMock
            ->expects($this->once())
            ->method('count')
            ->willReturn(0);

        $apiResponseMock = $this->createMock(UserCollectionResponse::class);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(204);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getElements')
            ->willReturn($elementCollectionMock);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('getPartnerUsers')
            ->with(
                GetPartnerUsersFilter::create()
                    ->setEmail('email@email.com')
                    ->addEmbedded(UserEmbeddedEntities::SUBSCRIPTIONS),
                $this->equalTo(null)
            )
            ->willReturn($apiResponseMock);

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->getSubscriptionResourceUrlByUserEmail('email@email.com');
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfSubscriptionNotCreatedOnGetSubscriptionResourceUrlByUserEmail(): void
    {
        $this->expectException(TradelineLogicException::class);
        $this->expectErrorMessage('Subscription was not created.');

        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->once())
            ->method('isEmpty')
            ->willReturn(true);
        $errorCollectionMock
            ->expects($this->never())
            ->method('__toString');

        $subscriptionCollectionMock = $this->createMock(ArrayCollection::class);
        $subscriptionCollectionMock
            ->expects($this->once())
            ->method('count')
            ->willReturn(0);

        $userMock = $this->createMock(User::class);
        $userMock
            ->expects($this->atLeastOnce())
            ->method('getSubscriptions')
            ->willReturn($subscriptionCollectionMock);

        $elementCollectionMock = $this->createMock(ArrayCollection::class);
        $elementCollectionMock
            ->expects($this->once())
            ->method('count')
            ->willReturn(1);
        $elementCollectionMock
            ->expects($this->once())
            ->method('first')
            ->willReturn($userMock);

        $apiResponseMock = $this->createMock(UserCollectionResponse::class);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(200);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getElements')
            ->willReturn($elementCollectionMock);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('getPartnerUsers')
            ->with(
                GetPartnerUsersFilter::create()
                    ->setEmail('email@email.com')
                    ->addEmbedded(UserEmbeddedEntities::SUBSCRIPTIONS),
                $this->equalTo(null)
            )
            ->willReturn($apiResponseMock);

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->getSubscriptionResourceUrlByUserEmail('email@email.com');
    }

    /**
     * @test
     */
    public function shouldReturnSubscriptionResourceUrlOnGetSubscriptionResourceUrlByUserEmail(): void
    {
        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->once())
            ->method('isEmpty')
            ->willReturn(true);
        $errorCollectionMock
            ->expects($this->never())
            ->method('__toString');

        $subscriptionMock = $this->createMock(Subscription::class);
        $subscriptionMock
            ->expects($this->once())
            ->method('getUrl')
            ->willReturn('http://some.url/resource_subscription/123');

        $subscriptionCollectionMock = $this->createMock(ArrayCollection::class);
        $subscriptionCollectionMock
            ->expects($this->once())
            ->method('count')
            ->willReturn(1);
        $subscriptionCollectionMock
            ->expects($this->atLeastOnce())
            ->method('first')
            ->willReturn($subscriptionMock);

        $userMock = $this->createMock(User::class);
        $userMock
            ->expects($this->atLeastOnce())
            ->method('getSubscriptions')
            ->willReturn($subscriptionCollectionMock);

        $elementCollectionMock = $this->createMock(ArrayCollection::class);
        $elementCollectionMock
            ->expects($this->once())
            ->method('count')
            ->willReturn(1);
        $elementCollectionMock
            ->expects($this->once())
            ->method('first')
            ->willReturn($userMock);

        $apiResponseMock = $this->createMock(UserCollectionResponse::class);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(200);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getElements')
            ->willReturn($elementCollectionMock);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('getPartnerUsers')
            ->with(
                GetPartnerUsersFilter::create()
                    ->setEmail('email@email.com')
                    ->addEmbedded(UserEmbeddedEntities::SUBSCRIPTIONS),
                $this->equalTo(null)
            )
            ->willReturn($apiResponseMock);

        $this->assertEquals(
            'http://some.url/resource_subscription/123',
            $this
                ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
                ->getSubscriptionResourceUrlByUserEmail('email@email.com')
        );
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenGetExceptionFromApiClientOnPayBackReportingProduct(): void
    {
        $this->expectException(TradelineClientException::class);
        $this->expectErrorMessage('Get error on pay product request: Some error on http request');

        $paymentSourceData = PaymentSourceDataRequest::create(
            BankAccount::create('holderName', 123678, '0557758', BankAccountType::CHECKING),
            PaymentAccountAddress::create('123 Test str.', 'Test City', 'TS', '99999')
        );

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('payProduct')
            ->with(
                $this->equalTo('productCode'),
                $this->equalTo(
                    PayProductRequest::create()
                        ->setAmount(1.01)
                        ->setObjectUrl('http://some.url/url/1')
                        ->setPaymentAccount(PaymentSourceDataRequestMapper::map($paymentSourceData))
                ),
                $this->equalTo(null)
            )
            ->willThrowException(new ClientException('Some error on http request'));

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->payProduct('productCode', 1.01, 'http://some.url/url/1', $paymentSourceData);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenFailedResponseOnPayBackReportingProduct(): void
    {
        $this->expectException(TradelineResponseException::class);
        $this->expectErrorMessage('Payment data account is invalid.');

        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->never())
            ->method('isEmpty');
        $errorCollectionMock
            ->expects($this->atLeastOnce())
            ->method('__toString')
            ->willReturn('Payment data account is invalid.');

        $apiResponseMock = $this->createMock(OrderResourceResponse::class);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(400);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);

        $paymentSourceData = PaymentSourceDataRequest::create(
            CreditCardAccount::create('holderName', '4556366726512358', new \DateTime('2030-12-31'), '123'),
            PaymentAccountAddress::create('123 Test str.', 'Test City', 'TS', '99999')
        );

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('payProduct')
            ->with(
                $this->equalTo('productCode'),
                $this->equalTo(
                    PayProductRequest::create()
                        ->setAmount(1.01)
                        ->setObjectUrl('http://some.url/url/1')
                        ->setPaymentAccount(PaymentSourceDataRequestMapper::map($paymentSourceData))
                ),
                $this->equalTo('valid_access_token')
            )
            ->willReturn($apiResponseMock);

        $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->payProduct('productCode', 1.01, 'http://some.url/url/1', $paymentSourceData, 'valid_access_token');
    }

    /**
     * @test
     */
    public function shouldReturnOrderResponseOnPayProduct(): void
    {
        $errorCollectionMock = $this->createMock(ErrorCollection::class);
        $errorCollectionMock
            ->expects($this->once())
            ->method('isEmpty')
            ->willReturn(true);
        $errorCollectionMock
            ->expects($this->never())
            ->method('__toString');

        $orderResourceMock = $this->createMock(Order::class);
        $orderResourceMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(321);
        $orderResourceMock
            ->expects($this->once())
            ->method('getReferenceId')
            ->willReturn('transaction_id_321');
        $orderResourceMock
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn(OrderStatus::COMPLETE);
        $orderResourceMock
            ->expects($this->once())
            ->method('getTotal')
            ->willReturn(3.29);
        $orderResourceMock
            ->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn($createdAt = new \DateTime());

        $apiResponseMock = $this->createMock(OrderResourceResponse::class);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(201);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getErrors')
            ->willReturn($errorCollectionMock);
        $apiResponseMock
            ->expects($this->atLeastOnce())
            ->method('getResource')
            ->willReturn($orderResourceMock);

        $paymentSourceData = PaymentSourceDataRequest::create(
            DebitCardAccount::create('holderName', '4556366726512358', new \DateTime('2030-12-31'), '322'),
            PaymentAccountAddress::create('123 Test str.', 'Test City', 'TS', '99999')
        );

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('payProduct')
            ->with(
                $this->equalTo('productCode'),
                $this->equalTo(
                    PayProductRequest::create()
                        ->setAmount(1.01)
                        ->setObjectUrl('http://some.url/url/1')
                        ->setPaymentAccount(PaymentSourceDataRequestMapper::map($paymentSourceData))
                ),
                $this->equalTo('valid_access_token')
            )
            ->willReturn($apiResponseMock);

        $orderResponse = $this
            ->setApiClient(new RequestHandler('main_client_id', 'main_client_secret'), $apiClientMock)
            ->payProduct('productCode', 1.01, 'http://some.url/url/1', $paymentSourceData, 'valid_access_token');

        $this->assertInstanceOf(OrderResponse::class, $orderResponse);
        $this->assertEquals(
            321,
            $orderResponse->getId()
        );
        $this->assertEquals(
            'transaction_id_321',
            $orderResponse->getReferenceId()
        );
        $this->assertEquals(
            OrderStatus::COMPLETE,
            $orderResponse->getStatus()
        );
        $this->assertEquals(
            3.29,
            $orderResponse->getAmount()
        );
        $this->assertEquals(
            $createdAt,
            $orderResponse->getCreatedAt()
        );
    }

    /**
     * @param RequestHandler $requestHandler
     * @param LevelCreditApiClient $apiClient
     * @return RequestHandler
     * @throws \ReflectionException
     */
    protected function setApiClient(RequestHandler $requestHandler, LevelCreditApiClient $apiClient): RequestHandler
    {
        $this->writeAttribute($requestHandler, 'apiClient', $apiClient);

        return $requestHandler;
    }
}
