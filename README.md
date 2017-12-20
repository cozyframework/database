# Cozy Database

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]


Powerful database toolkit for PHP 7+ that wraps PDO with many features to
provide an expressive query builder and data mapper. It also serves as the
database layer of the Cozy PHP Framework.

## Install

Via Composer

``` bash
$ composer require cozy/database
```

## Usage

``` php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=test', 'user', 'password');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  // optional
$db = new Cozy\Database\Connection($pdo);
$result = $db
    ->prepare('SELECT * FROM table WHERE id = ?')
    ->mapParams(['str'])
    ->bindValues(['aapg'])
    ->fetchAllAsArray();
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email info@nestorpicado.com instead of using the issue tracker.

## Credits

- [Nestor Picado][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/cozy/database.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/cozyframework/database/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/cozyframework/database.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/cozyframework/database.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/cozy/database.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/cozy/database
[link-travis]: https://travis-ci.org/cozyframework/database
[link-scrutinizer]: https://scrutinizer-ci.com/g/cozyframework/database/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/cozyframework/database
[link-downloads]: https://packagist.org/packages/cozy/database
[link-author]: https://github.com/npicado
[link-contributors]: ../../contributors
