# Doctrine Mysql Extra #

[![Latest Stable Version](https://poser.pugx.org/webonaute/doctrine-mysql-extra/v/stable.svg)](https://packagist.org/packages/webonaute/doctrine-mysql-extra) [![Total Downloads](https://poser.pugx.org/webonaute/doctrine-mysql-extra/downloads.svg)](https://packagist.org/packages/webonaute/doctrine-mysql-extra) [![Latest Unstable Version](https://poser.pugx.org/webonaute/doctrine-mysql-extra/v/unstable.svg)](https://packagist.org/packages/webonaute/doctrine-mysql-extra) [![License](https://poser.pugx.org/webonaute/doctrine-mysql-extra/license.svg)](https://packagist.org/packages/webonaute/doctrine-mysql-extra)

<!--ts-->
   * [Doctrine Mysql Extra](#doctrine-mysql-extra)
      * [About](#about)
      * [Release](#release)
      * [Installation](#installation)
      * [Documentation](#documentation)
         * [Doctrine configuration](#doctrine-configuration)
      * [License](#license)

<!-- Added by: mdelisle, at: Tue 25 Jun 2019 16:15:00 EDT -->

<!--te-->

## About ##

Some extra stuff for doctrine with MYSQL Plateform.

## Release ##

* Use version `1.x` for Doctrine 1.8+. [![build status](https://travis-ci.org/webonaute/DoctrineMysqlExtra.svg?branch=master)](https://travis-ci.org/webonaute/DoctrineMysqlExtra)

## Installation ##

This bundle is available via [composer](https://github.com/composer/composer), find it on [packagist](https://packagist.org/packages/webonaute/doctrine-mysql-extra).

Run : 
```composer require webonaute/doctrine-mysql-extra 1.0```

## Documentation ##

### Doctrine configuration

``` 
doctrine:
  dbal:
    default_connection: default
    connections:
      default:
        platform_service: 'Webonaute\DoctrineMysqlExtra\Doctrine\DBAL\Platforms\MySql57Platform'
        server_version: '5.7'
        #platform_service: 'Webonaute\DoctrineMysqlExtra\Doctrine\DBAL\Platforms\MySql80Platform'
        #server_version: '80'
    types:
      numeric: Webonaute\DoctrineMysqlExtra\Doctrine\DBAL\Types\NumericType
      cron_expression: Webonaute\DoctrineMysqlExtra\Doctrine\DBAL\Types\CronExpressionType
  orm:
    entity_managers:
      default:
        dql:
          datetime_functions:
            now: Webonaute\DoctrineMysqlExtra\Doctrine\ORM\Query\Now
            utc_timestamp: Webonaute\DoctrineMysqlExtra\Doctrine\ORM\Query\UtcTimestamp

       
```

For symfony User, please declare the service to use in your services.yml.
```
services:
    Webonaute\DoctrineMysqlExtra\Doctrine\DBAL\Platforms\MySql57Platform: ~
```

## License ##

See [LICENSE](LICENSE).
