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

// example of sync data
$syncDataJson = <<<JSON
[
  {
    "record_type" : "tradeline",
    "external_resident_id" : "<external_resident_id>", //required
    "first_name" : "<first_name>", //required
    "last_name" : "<last_name>", //required
    "birthdate" : "YYYY-MM-DD", //required
    "ssn" : "NNN-NN-NNNN", //required
    "email" : "RitaR@example.com", //required
    "mobile" : "NNN-NN-NNNN", //optional
 
    "external_lease_id" : "<external_lease_id>", //required
    "external_property_id" : "", //optional
    "address1" : "<address1 line>", //required
    "address2" : "<address2 line>", //optional
    "city" : "<city>", //required
    "state" : "<us state or canadian province>", //required
    "zip" : "<zip or postal code>", //required
    "country" : "US|CA", //optional by default 'US'
    "due_day" : <int due day 1-31>, // optional by default 1
    "start_at" : "YYYY-MM-DD", //required
    "rent" : "NNN.NN", //required
 
    "external_transaction_id" : "<external_transaction_id>", //required
    "type" : "rent|other", //required
    "amount" : "NNN.NN", //required
    "transaction_date" : "YYYY-MM-DD", //required
    "currency" : "usd|cad", //optional by default "usd"
   }
]
JSON;

try {
    $authResponse = $client->authenticate('<YOUR_USERNAME>', '<YOUR_PASSWORD>');
    
    $orderResponse = $client->purchaseBackreporting(
        $authResponse->getAccessToken(), 
        $syncDataJson,
        PaymentSourceDataRequest::create(
            CreditCardAccount::create(
               '<Your Credit Card Holder Name>',
               '<Your Credit Card Number>',
               new \DateTime('<Your Expiration Year>-<Your Expiration Month>-<Last day of this month>'), // example
               '<Your cvc>'
            ),
            PaymentAccountAddress::create(
                '<Your billing street address line>',
                '<Your billing address city>',
                '<Your billing address state>',
                '<Your billing address zip>'
            )
        )
    );
    
    echo $orderResponse->getReferenceId(); // transaction id
    echo $orderResponse->getStatus(); // order status
} catch (TradelineException $e) {
   // processing errors
}
```
