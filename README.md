[![SensioLabsInsight](https://insight.sensiolabs.com/projects/e329a3c0-005e-46b3-b17e-1044b48a90c7/big.png)](https://insight.sensiolabs.com/projects/e329a3c0-005e-46b3-b17e-1044b48a90c7)

# latch-sdk-php

Latch SDK php with Guzzle.

This library is 100% compatible with proyects with the original latch sdk https://github.com/ElevenPaths/latch-sdk-php. The feature of our library is that you can load it with the modern [Composer](http://getcomposer.org) system.

## Installation

Install via [Composer](http://getcomposer.org)

	{
	    "require": {
	        "fourcoders/latch-sdk-php": "dev-master"
	    }
	}

## Autoloading

Composer generates a vendor/autoload.php file. You can simply include this file and you will get autoloading for free.

```php
require 'vendor/autoload.php';
```

## Usage ###(Extract and modify of original php sdk https://github.com/ElevenPaths/latch-sdk-php#using-the-sdk-in-php)

Create a Latch object with the "Application ID" and "Secret" previously obtained.

```php
	$api = new \Fourcoders\LatchSdk\Latch(APP_ID, APP_SECRET);
```

Optional settings:

```php
	$api->setProxy(YOUR_PROXY);
```

Call to Latch Server. Pairing will return an account id that you should store for future api calls

```php
     $pairResponse = $api->pair("PAIRING_CODE_HERE");
     $statusResponse = $api->status(ACCOUNT_ID_HERE);
     $unpairResponse = $api->unpair(ACCOUNT_ID_HERE);
```

After every API call, get Latch response data and errors and handle them.

```php
     $pairResponse->getData();
     $pairResponse->getError();
