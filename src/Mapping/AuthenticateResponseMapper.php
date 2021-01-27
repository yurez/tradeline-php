<?php

namespace LevelCredit\Tradeline\Mapping;

use LevelCredit\LevelCreditApi\Model\Response\AccessTokenResponse;
use LevelCredit\Tradeline\Model\AuthenticateResponse;

class AuthenticateResponseMapper
{
    /**
     * @param AccessTokenResponse $response
     * @return AuthenticateResponse
     */
    public static function map(AccessTokenResponse $response): AuthenticateResponse
    {
        return new AuthenticateResponse(
            $response->getResource()->getAccessToken(),
            $response->getResource()->getRefreshToken(),
            $response->getResource()->getExpiresIn()
        );
    }
}
