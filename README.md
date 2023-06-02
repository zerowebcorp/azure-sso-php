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
     'clientId' => 'client id',
    'clientSecret' => 'client secret',
    'tenantId' => 'tenant id',
    'scope' => 'User.Read GroupMember.Read.All',
    'returnUrl' => 'https://localhost/sso',
    'returnGroups' => true, // set false to not return the user groups. If set to true, requires GroupMemeber.Read.All scope
];

$sso = new AzureSSO($config, $request);
try {
    $sso->authenticate(); 
    // The user is authenticated at this point and the session contains the auth data.
} catch (Exception $e) {
    echo ("There was a problem with authentication." . $e->getMessage());
}
```
