<?php

namespace LevelCredit\Tradeline\Tests\Unit\Mapping;

use LevelCredit\LevelCreditApi\Model\Response\AccessTokenResponse;
use LevelCredit\LevelCreditApi\Model\Response\Resource\AccessToken;
use LevelCredit\Tradeline\Mapping\AuthenticateResponseMapper;
use LevelCredit\Tradeline\Model\AuthenticateResponse;
use PHPUnit\Framework\TestCase;

class AuthenticateResponseMapperTest extends TestCase
{
    /**
     * @test
     */
    public function shouldMapApiAccessTokenResponseToAuthenticateResponse(): void
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

        $apiResponseMock = $this->createMock(AccessTokenResponse::class);
        $apiResponseMock
            ->expects($this->exactly(3))
            ->method('getResource')
            ->willReturn($accessTokenMock);

        $result = AuthenticateResponseMapper::map($apiResponseMock);

        $this->assertInstanceOf(AuthenticateResponse::class, $result);
        $this->assertEquals('some_access_token', $result->getAccessToken());
        $this->assertEquals('some_refresh_token', $result->getRefreshToken());
        $this->assertEquals(3600, $result->getExpiresIn());
    }
}
