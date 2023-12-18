# Integrate Zephyr into Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/redberryproducts/laravel-zephyr.svg?style=flat-square)](https://packagist.org/packages/redberryproducts/laravel-zephyr)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/redberryproducts/laravel-zephyr/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/redberryproducts/laravel-zephyr/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/redberryproducts/laravel-zephyr/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/redberryproducts/laravel-zephyr/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/redberryproducts/laravel-zephyr.svg?style=flat-square)](https://packagist.org/packages/redberryproducts/laravel-zephyr)

This package allows you to integrate Zephyr Test Suite into Laravel.

## Installation

You can install the package via composer:

```bash
composer require redberryproducts/laravel-zephyr
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-zephyr-config"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$zephyr = new RedberryProducts\Zephyr();
echo $zephyr->echoPhrase('Hello, RedberryProducts!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

- [RedberryProducts](https://github.com/RedberryProducts)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
