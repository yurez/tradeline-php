<?php

namespace LevelCredit\Tradeline;

use LevelCredit\Tradeline\Model\AuthenticateResponse;
use LevelCredit\Tradeline\Model\OrderResponse;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class TradelineClient implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param mixed ...$credentials here you can pass [string $username, string $password] or [string $refreshToken]
     * @return AuthenticateResponse
     */
    public function authenticate(string $clientId, string $clientSecret, ...$credentials): AuthenticateResponse
    {

    }

    public function purchaseBackreporting(
        string $accessToken,
        string $syncDataJson,
        string $paymentSourceDataJson
    ): OrderResponse {

    }
}
