<?php

declare(strict_types=1);

namespace Happyr\ElasticaDsn;

use Happyr\ElasticaDsn\Exception\InvalidArgumentException;
use Elastica\Client;

/**
 * Creates a Elastic search client.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Rob Frawley 2nd <rmf@src.run>
 * @author Nicolas Grekas <p@tchwork.com>
 */
final class ClientFactory
{
    public static function create($servers, array $options = []): Client
    {
        return new Client(self::getConfig($servers, $options));
    }

    /**
     * @param string|string[] $servers An array of servers, a DSN, or an array of DSNs
     * @param array           $options Valid keys are "username" and "password"
     */
    public static function getConfig($servers, array $options = []): array
    {
        if (\is_string($servers)) {
            $servers = [$servers];
        } elseif (!\is_array($servers)) {
            throw new InvalidArgumentException(\sprintf('ClientFactory::create() expects array or string as first argument, %s given.', \gettype($servers)));
        }

        \set_error_handler(function ($type, $msg, $file, $line) {
            throw new \ErrorException($msg, 0, $type, $file, $line);
        });
        try {
            return self::doGetConfig($servers, $options);
        } finally {
            \restore_error_handler();
        }
    }

    private static function doGetConfig(array $input, array $options): array
    {
        $servers = [];
        $username = $options['username'] ?? null;
        $password = $options['password'] ?? null;

        // parse any DSN in $servers
        foreach ($input as $i => $dsn) {
            if (\is_array($dsn)) {
                continue;
            }
            if (0 !== \mb_strpos($dsn, 'elasticsearch:')) {
                throw new InvalidArgumentException(
                    \sprintf('Invalid Elasticsearch DSN: %s does not start with "elasticsearch:"', $dsn)
                );
            }
            $params = \preg_replace_callback(
                '#^elasticsearch:(//)?(?:([^@]*+)@)?#',
                function ($m) use (&$username, &$password) {
                    if (!empty($m[2])) {
                        list($username, $password) = \explode(':', $m[2], 2) + [1 => null];
                    }

                    return 'file:'.($m[1] ?? '');
                },
                $dsn
            );

            if (false === $params = \parse_url($params)) {
                throw new InvalidArgumentException(\sprintf('Invalid Elasticsearch DSN: %s', $dsn));
            }

            $query = $hosts = [];
            if (isset($params['query'])) {
                \parse_str($params['query'], $query);

                if (isset($query['host'])) {
                    if (!\is_array($hosts = $query['host'])) {
                        throw new InvalidArgumentException(\sprintf('Invalid Elasticsearch DSN: %s', $dsn));
                    }
                    foreach ($hosts as $host => $value) {
                        if (false === $port = \mb_strrpos($host, ':')) {
                            $hosts[$host] = ['host' => $host, 'port' => 9200];
                        } else {
                            $hosts[$host] = ['host' => \mb_substr($host, 0, $port), 'port' => (int) \mb_substr($host, 1 + $port)];
                        }
                    }
                    $hosts = \array_values($hosts);
                    unset($query['host']);
                }
                if ($hosts && !isset($params['host']) && !isset($params['path'])) {
                    $servers = \array_merge($servers, $hosts);
                    continue;
                }
            }

            if (!isset($params['host']) && !isset($params['path'])) {
                throw new InvalidArgumentException(\sprintf('Invalid Elasticsearch DSN: %s', $dsn));
            }

            if (isset($params['path']) && \preg_match('#/(\d+)$#', $params['path'], $m)) {
                $params['path'] = \mb_substr($params['path'], 0, -\mb_strlen($m[0]));
            }

            if (isset($params['path']) && \preg_match('#:(\d+)$#', $params['path'], $m)) {
                $params['host'] = \mb_substr($params['path'], 0, -\mb_strlen($m[0]));
                $params['port'] = $m[1];
                unset($params['path']);
            }

            $params += [
                'host' => $params['host'] ?? $params['path'],
                'port' => !isset($params['port']) ? 9200 : null,
            ];
            if ($query) {
                $params += $query;
                $options = $query + $options;
            }

            $servers[] = ['host' => $params['host'], 'port' => $params['port']];

            if ($hosts) {
                $servers = \array_merge($servers, $hosts);
            }
        }

        $config = ['servers' => $servers];
        if (null !== $username) {
            $config['username'] = $username;
        }
        if (null !== $password) {
            $config['password'] = $password;
        }

        return $config;
    }
}
