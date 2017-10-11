[![Latest Stable Version](https://img.shields.io/packagist/v/drupol/psrcas.svg?style=flat-square)](https://packagist.org/packages/drupol/psrcas)
 [![GitHub stars](https://img.shields.io/github/stars/drupol/psrcas.svg?style=flat-square)](https://packagist.org/packages/drupol/psrcas)
 [![Total Downloads](https://img.shields.io/packagist/dt/drupol/psrcas.svg?style=flat-square)](https://packagist.org/packages/drupol/psrcas)
 [![Build Status](https://img.shields.io/travis/drupol/psrcas/master.svg?style=flat-square)](https://travis-ci.org/drupol/psrcas)
 [![Scrutinizer code quality](https://img.shields.io/scrutinizer/quality/g/drupol/psrcas/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/drupol/psrcas/?branch=master)
 [![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/drupol/psrcas/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/drupol/psrcas/?branch=master)
 [![Mutation testing badge](https://badge.stryker-mutator.io/github.com/drupol/psrcas/master)](https://stryker-mutator.github.io)
 [![License](https://img.shields.io/packagist/l/drupol/psrcas.svg?style=flat-square)](https://packagist.org/packages/drupol/psrcas)
 [![Say Thanks!](https://img.shields.io/badge/Say-thanks-brightgreen.svg?style=flat-square)](https://saythanks.io/to/drupol)
 [![Donate!](https://img.shields.io/badge/Donate-Paypal-brightgreen.svg?style=flat-square)](https://paypal.me/drupol)
 
# PSR CAS

PSR CAS, a standard PHP library for [CAS authentication](https://en.wikipedia.org/wiki/Central_Authentication_Service).

For improving the flexibility and in order to maximize it, it is able to authenticate users and leaves the session
handling up to the developer.

In order to foster a greater adoption of this library, it has been built with interoperability in mind.
It only uses [PHP Standards Recommendations](https://www.php-fig.org/) interfaces.

Therefore, this library is framework agnostic and uses only standard PSR interfaces for communication:

* [PSR-7](https://www.php-fig.org/psr/psr-7/) for Requests, Responses and URI.
* [PSR-17](https://www.php-fig.org/psr/psr-17/) for Requests, Responses and URI factories.
* [PSR-18](https://www.php-fig.org/psr/psr-18/) for HTTP client.
* [PSR-12](https://www.php-fig.org/psr/psr-12/) for coding standards.

## Installation

```bash
composer require drupol/psrcas
```

## Configuration

| Parameter  | Description |
|------------|-------------|
| `base_url` | The CAS service base URL. |
| `redirect_after_logout` | Redirect the user to that URL if no service parameter is provided after logout. |
| `protocol` | The CAS protocol specification. |

### Example

This is an example of a configuration in YAML, feel free to convert it in regular PHP array for PSR CAS.

```yaml
base_url: https://localhost:7002/cas
redirect_after_logout: http://localhost:8000/
protocol:
    login:
        path: /login
            allowed_parameters:
                - service
                - renew
                - gateway
        servicevalidate:
            path: /serviceValidate
            allowed_parameters:
                - service
                - ticket
                - pgtUrl
                - renew
                - format
        logout:
            path: /logout
            allowed_parameters:
                - service
```

## Usage

```
TODO
```

## Code style, code quality and tests

The code style is following [PSR-12](https://www.php-fig.org/psr/psr-12/) plus a set of custom rules,
the package [drupol/php-conventions](https://github.com/drupol/php-conventions) is responsible for this.

Every time changes are introduced into the library, [Travis CI](https://travis-ci.org/drupol/psrcas/builds) run the
tests.

The library has tests written with [PHPSpec](http://www.phpspec.net/).

[PHPInfection](https://github.com/infection/infection) is used to ensure that your code is properly tested.

To run the whole tests locally, run

```shell script
composer grumphp
```

## Contributing

See the file [CONTRIBUTING.md](.github/CONTRIBUTING.md) but feel free to contribute to this library by sending Github
pull requests.
