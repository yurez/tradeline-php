<?php

namespace LevelCredit\Tradeline;

use LevelCredit\Tradeline\Exception\TradelineException;
use LevelCredit\Tradeline\Exception\TradelineInvalidArgumentException;
use LevelCredit\Tradeline\Model\AuthenticateResponse;
use LevelCredit\Tradeline\Model\OrderResponse;
use LevelCredit\Tradeline\Model\PaymentSourceDataRequest;
use LevelCredit\Tradeline\RequestMediator\RequestMediator;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use function count;
use function preg_match;
use function sprintf;
use function trigger_error;

class TradelineClient
{
    private const BACKREPORTING_PRODUCT_CODE = 'LC-BACKREPORT';

    private const BACKREPORTING_PRODUCT_PRICE = 49.95;

    private const EMAIL_FIELD = 'email';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RequestMediator
     */
    protected $requestHandler;

    /**
     * @param string $clientId your levelcredit client_id parameter
     * @param string $clientSecret your levelcredit client_secret parameter
     * @param string|null $baseUrl by default will be use sandbox levelcredit url
     */
    public function __construct(
        string $clientId = '',
        string $clientSecret = '',
        string $baseUrl = null
    ) {
        $this->requestHandler = new RequestMediator($clientId, $clientSecret, $baseUrl);
        $this->requestHandler->setLogger($this->getLogger());
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
        $this->requestHandler->setBaseUri($baseUrl);

        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return static
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        $this->requestHandler->setLogger($logger);

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
        $this->getLogger()->debug('Authenticate ...');

        $countArgs = count($credentials);

        switch ($countArgs) {
            // on 3 or 1 number of arguments we should get access token by refresh token
            case 3:
                $this->getLogger()->debug('Authenticate with client_id and client_secret');

                list($clientId, $clientSecret, $refreshToken) = $credentials;

                return $this->requestHandler->getAccessTokenByRefreshToken($refreshToken, $clientId, $clientSecret);
            case 1:
                $this->getLogger()->debug('Authenticate by refresh_token');

                list($refreshToken) = $credentials;

                return $this->requestHandler->getAccessTokenByRefreshToken($refreshToken);
            // on 4 or 2 number of arguments we should get access token by username+password
            case 4:
                $this->getLogger()->debug('Authenticate with client_id and client_secret');

                list($clientId, $clientSecret, $username, $password) = $credentials;

                return $this->requestHandler->getAccessTokenByUsernamePassword(
                    $username,
                    $password,
                    $clientId,
                    $clientSecret
                );
            case 2:
                $this->getLogger()->debug('Authenticate by username+password');

                list($username, $password) = $credentials;

                return $this->requestHandler->getAccessTokenByUsernamePassword($username, $password);
            default:
                trigger_error(
                    sprintf('Wrong number of arguments passed to %s', __METHOD__),
                    E_USER_ERROR
                );
        }
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
        $this->getLogger()->debug('Purchase Backreporting...');

        $this->requestHandler->setAccessToken($accessToken);

        $syncId = $this->requestHandler->createTradelineSync();
        $this->getLogger()->debug(sprintf('Tradeline Sync with id "%s" created successfully.', $syncId));

        $this->requestHandler->addDataToTradelineSync($syncId, $syncDataJson);
        $this->getLogger()->debug('Tradeline Sync Data added successfully.');

        $this->requestHandler->startTradelineSync($syncId);
        $this->getLogger()->debug('Tradeline Sync Data is imported successfully.');

        $email = $this->getEmailFromSyncDataJson($syncDataJson);
        $subscriptionResourceUrl = $this->requestHandler->getSubscriptionResourceUrlByUserEmail($email);
        $this->getLogger()->debug('Subscription resource url was gotten successfully.');

        $orderResponse = $this->requestHandler->payProduct(
            self::BACKREPORTING_PRODUCT_CODE,
            self::BACKREPORTING_PRODUCT_PRICE,
            $subscriptionResourceUrl,
            $paymentSourceData
        );
        $this->getLogger()->debug('Product was payed successfully.');

        return $orderResponse;
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
        $this->logger->pushHandler(new StreamHandler('php://stdout'));

        return $this->logger;
    }

    /**
     * @param string $syncDataJson
     * @return string
     * @throws TradelineInvalidArgumentException
     */
    protected function getEmailFromSyncDataJson(string $syncDataJson): string
    {
        if (!preg_match(sprintf('/"%s".?:.?"(.*?)"/i', self::EMAIL_FIELD), $syncDataJson, $matches)) {
            throw new TradelineInvalidArgumentException('Email should be present in sync data.');
        }

        return $matches[1];
    }
}
