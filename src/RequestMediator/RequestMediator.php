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

namespace LevelCredit\Tradeline\RequestMediator;

use LevelCredit\LevelCreditApi\Enum\TradelineSyncType;
use LevelCredit\LevelCreditApi\Enum\UserEmbeddedEntities;
use LevelCredit\LevelCreditApi\Exception\LevelCreditApiException;
use LevelCredit\LevelCreditApi\LevelCreditApiClient;
use LevelCredit\LevelCreditApi\Logging\DefaultLogHandler;
use LevelCredit\LevelCreditApi\Model\Request\CreateTradelineSyncRequest;
use LevelCredit\LevelCreditApi\Model\Request\GetPartnerUsersFilter;
use LevelCredit\LevelCreditApi\Model\Request\PatchTradelineSyncRequest;
use LevelCredit\LevelCreditApi\Model\Request\PayProductRequest;
use LevelCredit\LevelCreditApi\Model\Response\BaseResponse;
use LevelCredit\LevelCreditApi\Model\Response\Resource\Subscription;
use LevelCredit\LevelCreditApi\Model\Response\Resource\User;
use LevelCredit\Tradeline\Exception\TradelineClientException;
use LevelCredit\Tradeline\Exception\TradelineException;
use LevelCredit\Tradeline\Exception\TradelineLogicException;
use LevelCredit\Tradeline\Exception\TradelineResponseException;
use LevelCredit\Tradeline\Mapping\AuthenticateResponseMapper;
use LevelCredit\Tradeline\Mapping\OrderResponseMapper;
use LevelCredit\Tradeline\Mapping\PaymentSourceDataRequestMapper;
use LevelCredit\Tradeline\Model\AuthenticateResponse;
use LevelCredit\Tradeline\Model\OrderResponse;
use LevelCredit\Tradeline\Model\PaymentSourceDataRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class RequestMediator
{
    private const FAILED_STATUS_ENTRY_POINT = 400;

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $clientSecret;

    /**
     * @var LevelCreditApiClient
     */
    protected $apiClient;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $logLevel = LogLevel::DEBUG;

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string|null $baseUrl
     */
    public function __construct(string $clientId = '', string $clientSecret = '', string $baseUrl = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        $this->apiClient = LevelCreditApiClient::create($clientId, $clientSecret)
            ->addLogHandler(
                DefaultLogHandler::create($this->getLogger())
                    ->setLogLevel($this->logLevel)
            );
        !$baseUrl || $this->apiClient->setBaseUri($baseUrl);
    }

    /**
     * @param string $baseUrl
     * @return static
     */
    public function setBaseUri(string $baseUrl): self
    {
        $this->apiClient->setBaseUri($baseUrl);

        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return static
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        $this->apiClient->disableLogHandlers()->addLogHandler(
            DefaultLogHandler::create($this->logger)->setLogLevel($this->logLevel)
        );

        return $this;
    }

    /**
     * @param string $accessToken
     * @return static
     */
    public function setAccessToken(string $accessToken): self
    {
        $this->apiClient->setAccessToken($accessToken);

        return $this;
    }

    /**
     * @param string $username
     * @param string $password
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return AuthenticateResponse
     * @throws TradelineException
     */
    public function getAccessTokenByUsernamePassword(
        string $username,
        string $password,
        string $clientId = null,
        string $clientSecret = null
    ): AuthenticateResponse {
        $this->getLogger()->debug('Handle "getAccessTokenByUsernamePassword" request');

        if ($clientId) {
            $this->apiClient->setClientId($clientId);
        }
        if ($clientSecret) {
            $this->apiClient->setClientSecret($clientSecret);
        }
        try {
            $response = $this->apiClient->getAccessTokenByUsernamePassword($username, $password);

            if (!$this->isSuccessApiResponse($response)) {
                $errorMessages = (string)$response->getErrors();
                $this->getLogger()->error('Authentication failed: ' . $errorMessages);

                throw new TradelineResponseException($errorMessages);
            }
        } catch (LevelCreditApiException $e) {
            $this->getLogger()->error($message = 'Get error on authenticate request: ' . $e->getMessage());

            throw new TradelineClientException($message);
        } finally {
            $this->resetClientIdAndClientSecret();
        }

        $this->getLogger()->debug('Authentication successful.');

        return AuthenticateResponseMapper::map($response);
    }

    /**
     * @param string $refreshToken
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return AuthenticateResponse
     * @throws TradelineException
     */
    public function getAccessTokenByRefreshToken(
        string $refreshToken,
        string $clientId = null,
        string $clientSecret = null
    ): AuthenticateResponse {
        $this->getLogger()->debug('Handle "getAccessTokenByRefreshToken" request');

        if ($clientId) {
            $this->apiClient->setClientId($clientId);
        }
        if ($clientSecret) {
            $this->apiClient->setClientSecret($clientSecret);
        }

        try {
            $response = $this->apiClient->getAccessTokenByRefreshToken($refreshToken);

            if (!$this->isSuccessApiResponse($response)) {
                $errorMessages = (string)$response->getErrors();
                $this->getLogger()->error('Authentication failed: ' . $errorMessages);

                throw new TradelineResponseException($errorMessages);
            }
        } catch (LevelCreditApiException $e) {
            $this->getLogger()->error($message = 'Get error on authenticate request: ' . $e->getMessage());

            throw new TradelineClientException($message);
        } finally {
            $this->resetClientIdAndClientSecret();
        }

        $this->getLogger()->debug('Authentication successful.');

        return AuthenticateResponseMapper::map($response);
    }

    /**
     * @param string|null $accessToken
     * @return int
     * @throws TradelineException
     */
    public function createTradelineSync(string $accessToken = null): int
    {
        $this->getLogger()->debug('Handle "createTradelineSync" request');

        try {
            $response = $this->apiClient->createTradelineSync(
                CreateTradelineSyncRequest::create()->setType(TradelineSyncType::SYNCHRONOUS),
                $accessToken
            );
        } catch (LevelCreditApiException $e) {
            $this->getLogger()->error($message = 'Get error on create tradeline sync request: ' . $e->getMessage());

            throw new TradelineClientException($message);
        }

        if (!$this->isSuccessApiResponse($response)) {
            $errorMessages = (string)$response->getErrors();
            $this->getLogger()->error('Creating tradeline sync failed: ' . $errorMessages);

            throw new TradelineResponseException($errorMessages);
        }

        return $response->getResource()->getId();
    }

    /**
     * @param int $syncId
     * @param string $syncDataJson
     * @param string|null $accessToken
     * @throws TradelineException
     */
    public function addDataToTradelineSync(int $syncId, string $syncDataJson, string $accessToken = null): void
    {
        $this->getLogger()->debug('Handle "addDataToTradelineSync" request');

        try {
            $response = $this->apiClient->addDataToTradelineSync($syncId, $syncDataJson, $accessToken);
        } catch (LevelCreditApiException $e) {
            $this->getLogger()->error(
                $message = 'Get error on add data to tradeline sync request: ' . $e->getMessage()
            );

            throw new TradelineClientException($message);
        }

        if (!$this->isSuccessApiResponse($response)) {
            $errorMessages = (string)$response->getErrors();
            $this->getLogger()->error('Adding data to tradeline sync failed: ' . $errorMessages);

            throw new TradelineResponseException($errorMessages);
        }
    }

    /**
     * @param int $syncId
     * @param string|null $accessToken
     * @throws TradelineException
     */
    public function startTradelineSync(int $syncId, string $accessToken = null): void
    {
        $this->getLogger()->debug('Handle "startTradelineSync" request');

        try {
            $response = $this->apiClient->patchTradelineSync(
                $syncId,
                PatchTradelineSyncRequest::create(),
                $accessToken
            );
        } catch (LevelCreditApiException $e) {
            $this->getLogger()->error($message = 'Get error on start tradeline sync request: ' . $e->getMessage());

            throw new TradelineClientException($message);
        }

        if (!$this->isSuccessApiResponse($response)) {
            $errorMessages = (string)$response->getErrors();
            $this->getLogger()->error('Starting tradeline sync failed: ' . $errorMessages);

            throw new TradelineResponseException($errorMessages);
        }
    }

    /**
     * @param string $email
     * @param string|null $accessToken
     * @return string
     * @throws TradelineException
     */
    public function getSubscriptionResourceUrlByUserEmail(string $email, string $accessToken = null): string
    {
        $this->getLogger()->debug('Handle "getSubscriptionResourceUrlByUserEmail" request');

        try {
            $response = $this->apiClient->getPartnerUsers(
                GetPartnerUsersFilter::create()
                    ->setEmail($email)
                    ->addEmbedded(UserEmbeddedEntities::SUBSCRIPTIONS),
                $accessToken
            );
        } catch (LevelCreditApiException $e) {
            $this->getLogger()->error($message = 'Get error on get subscription url request: ' . $e->getMessage());

            throw new TradelineClientException($message);
        }


        if (!$this->isSuccessApiResponse($response)) {
            $errorMessages = (string)$response->getErrors();
            $this->getLogger()->error('Getting subscription url failed: ' . $errorMessages);

            throw new TradelineResponseException($errorMessages);
        }

        if ($response->getElements()->count() != 1) {
            $this->getLogger()->error('Getting subscription url failed: User was not imported correct.');

            throw new TradelineLogicException('User was not imported correct.');
        }
        /** @var User $user */
        $user = $response->getElements()->first();

        if ($user->getSubscriptions()->count() != 1) {
            $this->getLogger()->error('Getting subscription url failed: Subscription was not created.');

            throw new TradelineLogicException('Subscription was not created.');
        }
        /** @var Subscription $subscription */
        $subscription = $user->getSubscriptions()->first();

        return $subscription->getUrl();
    }

    /**
     * @param string $productCode
     * @param float $amount
     * @param string $objectResourceUrl
     * @param PaymentSourceDataRequest $paymentSource
     * @param string|null $accessToken
     * @return OrderResponse
     * @throws TradelineException
     */
    public function payProduct(
        string $productCode,
        float $amount,
        string $objectResourceUrl,
        PaymentSourceDataRequest $paymentSource,
        string $accessToken = null
    ): OrderResponse {
        $this->getLogger()->debug('Handle "payBackReportingProduct" request');

        try {
            $response = $this->apiClient->payProduct(
                $productCode,
                PayProductRequest::create()
                    ->setAmount($amount)
                    ->setObjectUrl($objectResourceUrl)
                    ->setPaymentAccount(
                        PaymentSourceDataRequestMapper::map($paymentSource)
                    ),
                $accessToken
            );
        } catch (LevelCreditApiException $e) {
            $this->getLogger()->error($message = 'Get error on pay product request: ' . $e->getMessage());

            throw new TradelineClientException($message);
        }

        if (!$this->isSuccessApiResponse($response)) {
            $errorMessages = (string)$response->getErrors();
            $this->getLogger()->error('Paying product failed: ' . $errorMessages);

            throw new TradelineResponseException($errorMessages);
        }

        return OrderResponseMapper::map($response);
    }

    /**
     * @param BaseResponse $response
     * @return bool
     */
    protected function isSuccessApiResponse(BaseResponse $response): bool
    {
        if ($response->getStatusCode() >= self::FAILED_STATUS_ENTRY_POINT || !$response->getErrors()->isEmpty()) {
            return false;
        }

        return true;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger(): LoggerInterface
    {
        if ($this->logger) {
            return $this->logger;
        }
        $this->logger = new NullLogger();

        return $this->logger;
    }

    protected function resetClientIdAndClientSecret(): void
    {
        $this->apiClient->setClientId($this->clientId);
        $this->apiClient->setClientSecret($this->clientSecret);
    }
}
