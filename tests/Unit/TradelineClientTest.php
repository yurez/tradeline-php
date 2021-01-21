<?php

namespace LevelCredit\Tradeline\Tests\Unit;

use LevelCredit\LevelCreditApi\Exception\ClientException;
use LevelCredit\LevelCreditApi\LevelCreditApiClient;
use LevelCredit\LevelCreditApi\Model\Response\AccessTokenResponse;
use LevelCredit\LevelCreditApi\Model\Response\ErrorCollection;
use LevelCredit\LevelCreditApi\Model\Response\Resource\AccessToken;
use LevelCredit\Tradeline\Exception\TradelineClientException;
use LevelCredit\Tradeline\Exception\TradelineResponseException;
use LevelCredit\Tradeline\Model\AuthenticateResponse;
use LevelCredit\Tradeline\TradelineClient;
use PHPUnit\Framework\TestCase;

class TradelineClientTest extends TestCase
{
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
    public function shouldThrowExceptionWhenGetExceptionFromApiClientOnAuthenticateByRefreshToken(): void
    {
        $this->expectException(TradelineClientException::class);
        $this->expectErrorMessage('Get error on authenticate request: Some error on http request');

        $apiClientMock = $this->createMock(LevelCreditApiClient::class);
        $apiClientMock
            ->expects($this->once())
            ->method('getAccessTokenByRefreshToken')
            ->willThrowException(new ClientException('Some error on http request'));

        TradelineClient::create()->setApiClient($apiClientMock)->authenticate('some_refresh_token');
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
            ->expects($this->once())
            ->method('getAccessTokenByUsernamePassword')
            ->willThrowException(new ClientException('Some error on http request'));

        TradelineClient::create()->setApiClient($apiClientMock)->authenticate('some_username', 'some_password');
    }

    /**
     * @test
     */
    public function shouldGetAccessTokenByClientIdClientSecretRefreshTokenOnAuthenticate(): void
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
            ->with('client_id')
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->once())
            ->method('setClientSecret')
            ->with('client_secret')
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->once())
            ->method('getAccessTokenByRefreshToken')
            ->with('some_refresh_token')
            ->willReturn($apiResponseMock);

        $result = TradelineClient::create()
            ->setApiClient($apiClientMock)
            ->authenticate('client_id', 'client_secret', 'some_refresh_token');

        $this->assertInstanceOf(AuthenticateResponse::class, $result);
        $this->assertEquals('some_access_token', $result->getAccessToken());
        $this->assertEquals('some_refresh_token', $result->getRefreshToken());
        $this->assertEquals(3600, $result->getExpiresIn());
    }

    /**
     * @test
     */
    public function shouldGetAccessTokenByRefreshTokenOnAuthenticate(): void
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
            ->method('getAccessTokenByRefreshToken')
            ->with('some_new_refresh_token')
            ->willReturn($apiResponseMock);

        $result = TradelineClient::create()
            ->setApiClient($apiClientMock)
            ->authenticate('some_new_refresh_token');

        $this->assertInstanceOf(AuthenticateResponse::class, $result);
        $this->assertEquals('some_access_token', $result->getAccessToken());
        $this->assertEquals('some_refresh_token', $result->getRefreshToken());
        $this->assertEquals(3600, $result->getExpiresIn());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnFailedResponseByRefreshTokenOnAuthenticate(): void
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
            ->expects($this->once())
            ->method('getAccessTokenByRefreshToken')
            ->with('some_new_refresh_token')
            ->willReturn($apiResponseMock);

        $result = TradelineClient::create()
            ->setApiClient($apiClientMock)
            ->authenticate('some_new_refresh_token');
    }

    /**
     * @test
     */
    public function shouldGetAccessTokenByClientIdClientSecretUsernamePasswordOnAuthenticate(): void
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
            ->with('client_id')
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->once())
            ->method('setClientSecret')
            ->with('client_secret')
            ->willReturn($apiClientMock);
        $apiClientMock
            ->expects($this->once())
            ->method('getAccessTokenByUsernamePassword')
            ->with('some_username', 'some_password')
            ->willReturn($apiResponseMock);

        $result = TradelineClient::create()
            ->setApiClient($apiClientMock)
            ->authenticate('client_id', 'client_secret', 'some_username', 'some_password');

        $this->assertInstanceOf(AuthenticateResponse::class, $result);
        $this->assertEquals('some_access_token', $result->getAccessToken());
        $this->assertEquals('some_refresh_token', $result->getRefreshToken());
        $this->assertEquals(3600, $result->getExpiresIn());
    }

    /**
     * @test
     */
    public function shouldGetAccessTokenByUsernamePasswordOnAuthenticate(): void
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
            ->method('getAccessTokenByUsernamePassword')
            ->with('some_username', 'some_password')
            ->willReturn($apiResponseMock);

        $result = TradelineClient::create()
            ->setApiClient($apiClientMock)
            ->authenticate('some_username', 'some_password');

        $this->assertInstanceOf(AuthenticateResponse::class, $result);
        $this->assertEquals('some_access_token', $result->getAccessToken());
        $this->assertEquals('some_refresh_token', $result->getRefreshToken());
        $this->assertEquals(3600, $result->getExpiresIn());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnFailedResponseByUsernamePasswordOnAuthenticate(): void
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
            ->expects($this->once())
            ->method('getAccessTokenByUsernamePassword')
            ->with('some_username', 'some_password')
            ->willReturn($apiResponseMock);

        $result = TradelineClient::create()
            ->setApiClient($apiClientMock)
            ->authenticate('some_username', 'some_password');
    }
}
