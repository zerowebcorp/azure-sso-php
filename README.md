# Azure SSO Integration 

Quickly integrate Azure Single Sign On

## Installation

Install the latest version with

```bash
$ composer require zeroweb/azuresso
```

## Basic Usage

```php
<?php

use Symfony\Component\HttpFoundation\Request;
use ZeroWeb\AzureSSO;
$request = Request::createFromGlobals();

## $azureCreds = array of the credenetials

$config = [
     'clientId' => $azureCreds['client_id'],
    'clientSecret' => $azureCreds['client_secret'],
    'tenantId' => $azureCreds['tenant_id'],
    'scope' => 'User.Read GroupMember.Read.All',
    'returnUrl' => 'https://localhost/sso',
    'returnGroups' => true,
];

$sso = new AzureSSO($config, $request);
try {
    $sso->authenticate();
} catch (Exception $e) {
    echo ("There was a problem with authentication." . $e->getMessage());
}
```
