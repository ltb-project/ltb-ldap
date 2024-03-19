# LDAP Tool Box PHP framework

[![Latest Stable Version](http://poser.pugx.org/ltb-project/ldap/v)](https://packagist.org/packages/ltb-project/ldap)
[![Latest Unstable Version](http://poser.pugx.org/ltb-project/ldap/v/unstable)](https://packagist.org/packages/ltb-project/ldap)
[![Total Downloads](http://poser.pugx.org/ltb-project/ldap/downloads)](https://packagist.org/packages/ltb-project/ldap)

## Presentation

This is a PHP library to share code between LTB applications like [Self Service Password](https://github.com/ltb-project/self-service-password), [White Pages](https://github.com/ltb-project/white-pages), [Service Desk](https://github.com/ltb-project/service-desk), ...

## Installation

Add the dependency in your [composer](https://getcomposer.org/) configuration:

```json
{
    "require": {
        "ltb-project/ldap": "v0.1"
    }
}
```

Then update dependencies:
```
composer update
```

Use autoloading in your code to load composer dependencies:
```php
require __DIR__ . 'vendor/autoload.php';
```

## Usage

### LDAP connection

```php
$ldap_url = "ldap://ldap.example.com";
$ldap_starttls = false;
$dn = "cn=admin,dc=example,dc=com";
$password = "secret";
$ldap_network_timeout = 3;

$ldap_connection = \Ltb\Ldap::connect($ldap_url, $ldap_starttls, $dn, $password, $ldap_network_timeout);

$ldap = $ldap_connection[0];
$result = $ldap_connection[1];

if (!$result) {
    error_log("Unable to connect to $ldap_url");
    exit 1;
}
```

## Tests

Make sure you have docker or podman installed

Get composer dependencies:

```
composer update
```

Run the tests (requires an internet connection for donwloading the openldap docker image):

```
./runtests
```

If you already have an openldap server, you can also adapt the tests in tests/ directory, and run them with:

```
vendor/bin/phpunit tests/Ltb
```


