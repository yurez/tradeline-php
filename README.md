# LevelCredit Tradeline SDK

## Installation

Install the latest version with

```bash
$ composer require levelcredit/tradeline-php 
```

## Basic Usage

```php
<?php

use LevelCredit\Tradeline\TradelineClient;
use LevelCredit\Tradeline\Exception\TradelineException
use LevelCredit\Tradeline\Model\PaymentSourceDataRequest;
use LevelCredit\Tradeline\Model\SubModel\CreditCardAccount;
use LevelCredit\Tradeline\Model\SubModel\PaymentAccountAddress;

$client = TradelineClient::create('<YOUR_CLIENT_ID>', '<YOUR_CLIENT_SECRET>')
//     ->setLogger(\Psr\Log\LoggerInterface $logger) // pass logger if need by default logs will be put to php://stdout
//     ->setBaseUrl(string $yourUrlHere) // pass base url if need by default use production level credit url   
;

$syncDataJson = '{json object here}';

try {
    $authResponse = $client->authenticate('<YOUR_USERNAME>', '<YOUR_PASSWORD>');
    
    $orderResposne = $client->purchaseBackreporting(
        $authResponse->getAccessToken(), 
        $syncDataJson,
        PaymentSourceDataRequest::create(
            CreditCardAccount::create(
               '<Your Credit Card Holder Name>',
               '<Your Credit Card Number>',
               new \DateTime('<Your Expiration Year>-<Your Expiration Month>-01'), // example
               '<Your cvc>'
            ),
            PaymentAccountAddress::create(
                '<Your billing street address line>',
                '<Your billing address city>',
                '<Your billing address zip>'
            )
        )
    );
    
    echo $orderResposne->getReferenceId(); // transaction id
    echo $orderResposne->getStatus(); // order status
} catch (TradelineException $e) {
   // processing errors
}
```
