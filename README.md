# Cozy Database

![PHP Version](https://img.shields.io/badge/php_version-7.1%2B-brightgreen.svg?longCache=true&style=flat-square)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://scrutinizer-ci.com/g/cozyframework/database/badges/build.png?b=v0.1)](https://scrutinizer-ci.com/g/cozyframework/database/build-status/v0.1)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/cozyframework/database/badges/quality-score.png?b=v0.1)](https://scrutinizer-ci.com/g/cozyframework/database/?branch=v0.1)
[![Code Coverage](https://scrutinizer-ci.com/g/cozyframework/database/badges/coverage.png?b=v0.1)](https://scrutinizer-ci.com/g/cozyframework/database/?branch=v0.1)
[![Total Downloads](https://img.shields.io/packagist/dt/cozy/database.svg?style=flat-square)](https://packagist.org/packages/cozy/database)


Database toolkit for PHP 7.1+ that encapsulates a PDO instance to simplify and improve its functionality, in addition
to allowing good security practices and providing an expressive query builder. This library is also a component of the
Cozy PHP Framework.

## Install

Via Composer

``` bash
$ composer require cozy/database
```

## Usage

Single connection to a relational database:

``` php
use \Cozy\Database\Relational\Connection;

$db = new Connection('mysql:host=localhost;port=3306;dbname=test', 'user', 'password');

$account = $db
    ->prepare('SELECT * FROM schema.accounts WHERE id = :id')
    ->bindValue(':id', '6b70a1f7-2a41-4da3-9fdb-f8b60273dec1', 'string')
    ->fetchAsObject(Account::class);
```

Pool of connections to relational databases:

``` php
use \Cozy\Database\Relational\ConnectionPool;
use \Cozy\Database\Relational\Connection;

$db_pool = new ConnectionPool(ConnectionPool::SELECTION_RANDOM);

foreach ($settings['database']['master'] as $database_info) {
    $db_pool->addConnection(Connection::fromArray($database_info), 'master');
}

foreach ($settings['database']['slave'] as $database_info) {
    $db_pool->addConnection(Connection::fromArray($database_info), 'slave');
}

$account = $db_pool->getConnection('slave')
    ->prepare('SELECT * FROM schema.accounts WHERE id = :id')
    ->bindValue(':id', '6b70a1f7-2a41-4da3-9fdb-f8b60273dec1', 'string')
    ->fetchAsObject(Account::class);
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

[link-author]: https://github.com/npicado
[link-contributors]: ../../contributors
