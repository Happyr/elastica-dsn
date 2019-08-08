# Ruflin Elastica client factory

[![Latest Version](https://img.shields.io/github/release/Happyr/elastica-dsn.svg?style=flat-square)](https://github.com/Happyr/elastica-dsn/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/Happyr/elastica-dsn.svg?style=flat-square)](https://travis-ci.org/Happyr/elastica-dsn)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/Happyr/elastica-dsn.svg?style=flat-square)](https://scrutinizer-ci.com/g/Happyr/elastica-dsn)
[![Quality Score](https://img.shields.io/scrutinizer/g/Happyr/elastica-dsn.svg?style=flat-square)](https://scrutinizer-ci.com/g/Happyr/elastica-dsn)
[![Total Downloads](https://img.shields.io/packagist/dt/happyr/elastica-dsn.svg?style=flat-square)](https://packagist.org/packages/happyr/elastica-dsn)

This package contains a factory method to create a Elasticsearch client from [ruflin/elastica](https://github.com/ruflin/Elastica).
The factory supports DSN to ease config with a dependency injection container. 

## Install

```
composer require happyr/elastica-dsn
```

## Examples

```php
use Happyr\ElasticaDsn\ClientFactory;

$client = ClientFactory::create('elasticsearch://localhost');
$client = ClientFactory::create('elasticsearch:?host[localhost]&host[localhost:9201]&host[127.0.0.1:9202]');
$client = ClientFactory::create('elasticsearch://foo:bar@localhost:1234');
$client = ClientFactory::create('elasticsearch://localhost:9201', ['username' => 'foo', 'password' => 'bar']);
```

If you use Symfony service config:

```yaml
services:
    Elastica\Client:
        factory: 'Happyr\ElasticaDsn\ClientFactory::create'
        arguments: ['elasticsearch://localhost']
```

If you want to configure the client even more, you may just get the config array from the `ClientFactory` and
instantiate the client yourself. 

```php
use Elastica\Client;
use Happyr\ElasticaDsn\ClientFactory;

$config = ClientFactory::getConfig('elasticsearch://localhost');

// Add more stuff to $config array
$client = new Client($config);
```
