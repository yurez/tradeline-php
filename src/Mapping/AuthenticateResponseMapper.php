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
