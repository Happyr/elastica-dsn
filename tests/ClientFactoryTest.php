<?php

declare(strict_types=1);

namespace Tests\Happyr\ElasticaDsn;

use Happyr\ElasticaDsn\ClientFactory;
use Nyholm\NSA;
use PHPUnit\Framework\TestCase;

class ClientFactoryTest extends TestCase
{
    /**
     * @dataProvider getServers
     */
    public function testGetConfig($servers, array $options, array $expected)
    {
        $output = NSA::invokeMethod(ClientFactory::class, 'doGetConfig', $servers, $options);
        $this->assertEquals($expected, $output);
    }

    public function getServers()
    {
        yield [['elasticsearch:localhost'], [], ['servers' => [['host' => 'localhost', 'port' => 9200]]]];
        yield [['elasticsearch://localhost'], [], ['servers' => [['host' => 'localhost', 'port' => 9200]]]];
        yield [['elasticsearch://example.com'], [], ['servers' => [['host' => 'example.com', 'port' => 9200]]]];
        yield [['elasticsearch://localhost:1234'], [], ['servers' => [['host' => 'localhost', 'port' => 1234]]]];

        yield [['elasticsearch://foo:bar@localhost:1234'], [], [
            'username' => 'foo',
            'password' => 'bar',
            'servers' => [['host' => 'localhost', 'port' => 1234]],
        ]];

        yield [['elasticsearch:?host[localhost]&host[localhost:9201]&host[127.0.0.1:9202]'], [], [
            'servers' => [
                ['host' => 'localhost', 'port' => 9200],
                ['host' => 'localhost', 'port' => 9201],
                ['host' => '127.0.0.1', 'port' => 9202],
            ],
        ]];
        yield [['elasticsearch:?host[localhost]&host[localhost:9201]&host[127.0.0.1:9202]', 'elasticsearch:localhost:1234'], [], [
            'servers' => [
                ['host' => 'localhost', 'port' => 9200],
                ['host' => 'localhost', 'port' => 9201],
                ['host' => '127.0.0.1', 'port' => 9202],
                ['host' => 'localhost', 'port' => 1234],
            ],
        ]];

        yield [['elasticsearch:foo:bar@?host[localhost:9201]'], [], [
            'username' => 'foo',
            'password' => 'bar',
            'servers' => [['host' => 'localhost', 'port' => 9201]],
        ]];
    }
}
