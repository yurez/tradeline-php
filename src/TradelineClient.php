<?php

namespace LevelCredit\Tradeline;

use LevelCredit\LevelCreditApi\Exception\LevelCreditApiException;
use LevelCredit\LevelCreditApi\LevelCreditApiClient;
use LevelCredit\LevelCreditApi\Logging\DefaultLogHandler;
use LevelCredit\LevelCreditApi\Model\Response\AccessTokenResponse;
use LevelCredit\LevelCreditApi\Model\Response\BaseResponse;
use LevelCredit\Tradeline\Exception\TradelineClientException;
use LevelCredit\Tradeline\Exception\TradelineException;
use LevelCredit\Tradeline\Exception\TradelineResponseException;
use LevelCredit\Tradeline\Mapping\AuthenticateResponseMapper;
use LevelCredit\Tradeline\Model\AuthenticateResponse;
use LevelCredit\Tradeline\Model\OrderResponse;
use LevelCredit\Tradeline\Model\PaymentSourceDataRequest;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class TradelineClient
{
    private const FAILED_STATUS_ENTRY_POINT = 400;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var LevelCreditApiClient
     */
    protected $apiClient;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $logLevel = LogLevel::DEBUG;

    /**
     * @param string $clientId your levelcredit client_id parameter
     * @param string $clientSecret your levelcredit client_secret parameter
     * @param string|null $baseUrl by default will be use production levelcredit url
     */
    public function __construct(
        string $clientId = '',
        string $clientSecret = '',
        string $baseUrl = null
    ) {
        $this->baseUrl = $baseUrl;
        $this->apiClient = LevelCreditApiClient::create($clientId, $clientSecret)
            ->addLogHandler(
                DefaultLogHandler::create($this->getLogger())
                    ->setLogLevel($this->logLevel)
            );
        !$this->baseUrl || $this->apiClient->setBaseUri($this->baseUrl);
    }

    /**
     * @param string $clientId your levelcredit client_id parameter
     * @param string $clientSecret your levelcredit client_secret parameter
     * @return static
     */
    public static function create(string $clientId = '', string $clientSecret = ''): self
    {
        return new static($clientId, $clientSecret);
    }

    /**
     * @param string $baseUrl
     * @return static
     */
    public function setBaseUrl(string $baseUrl): self
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
     * [string $username, string $password]
     * [string $refreshToken]
     * [string $clientId, string $clientSecret, string $username, string $password]
     * [string $clientId, string $clientSecret, string $refreshToken]
     *
     * @param mixed ...$credentials here you can pass [string $username, string $password] or [string $refreshToken]
     * @return AuthenticateResponse
     * @throws TradelineException
     */
    public function authenticate(...$credentials): AuthenticateResponse
    {
        $this->logger->debug('Authenticate ...');

        $countArgs = count($credentials);
        $username = $password = $refreshToken = null;

        switch ($countArgs) {
            // on 3 or 1 number of arguments we should get access token by refresh token
            case 3:
                $this->logger->debug('Authenticate with client_id and client_secret');

                list($clientId, $clientSecret, $refreshToken) = $credentials;
                $this->apiClient
                    ->setClientId($clientId)
                    ->setClientSecret($clientSecret);
            case 1:
                $this->logger->debug('Authenticate by refresh_token');

                $refreshToken || list($refreshToken) = $credentials;
                try {
                    $response = $this->apiClient->getAccessTokenByRefreshToken($refreshToken);
                } catch (LevelCreditApiException $e) {
                    throw new TradelineClientException('Get error on authenticate request: ' . $e->getMessage());
                }
                break;
            // on 4 or 2 number of arguments we should get access token by username+password
            case 4:
                $this->logger->debug('Authenticate with client_id and client_secret');

                list($clientId, $clientSecret, $username, $password) = $credentials;
                $this->apiClient
                    ->setClientId($clientId)
                    ->setClientSecret($clientSecret);
            case 2:
                $this->logger->debug('Authenticate by username+password');

                ($username && $password) || list($username, $password) = $credentials;
                try {
                    $response = $this->apiClient->getAccessTokenByUsernamePassword($username, $password);
                } catch (LevelCreditApiException $e) {
                    $this->logger->error('Get error from client on authenticate request: ' . $e->getMessage());

                    throw new TradelineClientException('Get error on authenticate request: ' . $e->getMessage());
                }
                break;
            default:
                trigger_error(
                    sprintf('Wrong number of arguments passed to %s', __METHOD__),
                    E_USER_ERROR
                );
        }

        return $this->processApiAuthenticateResponse($response);
    }

    /**
     * @param string $accessToken
     * @param string $syncDataJson
     * @param PaymentSourceDataRequest $paymentSourceData
     * @return OrderResponse
     * @throws TradelineException
     */
    public function purchaseBackreporting(
        string $accessToken,
        string $syncDataJson,
        PaymentSourceDataRequest $paymentSourceData
    ): OrderResponse {
        throw new TradelineClientException('Not implemented yet');
    }

    /**
     * @param AccessTokenResponse $response
     * @return AuthenticateResponse
     * @throws TradelineResponseException
     */
    protected function processApiAuthenticateResponse(AccessTokenResponse $response): AuthenticateResponse
    {
        if (!$this->isSuccessApiResponse($response)) {
            $errorMessages = (string)$response->getErrors();
            $this->logger->error('Authentication failed: ' . $errorMessages);

            throw new TradelineResponseException($errorMessages);
        }

        $this->logger->debug('Authentication successful.');

        return AuthenticateResponseMapper::map($response);
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

        $this->logger = new Logger(__CLASS__);
        $this->logger->pushHandler(new StreamHandler('php://stdout', $this->logLevel));

        return $this->logger;
    }
}
