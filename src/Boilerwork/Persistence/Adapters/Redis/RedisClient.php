#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Persistence\Adapters\Redis;

use Boilerwork\Persistence\Pools\RedisPool;
use Redis;

/**
 * Open a pool of connections to Redis server, so we can use them when needed.
 *
 * @example []
        $redisClient = new RedisClient();
        $redisClient->getConnection();
        $redisClient->hSet('SetOneKey', 'hashKey', json_encode(['foo' => 'bar']));
        var_dump($redisClient->hGet('SetOneKey', 'hashKey'));
        $redisClient->putConnection();  // Connection must be released
 **/
/**
 *
 * @deprecated Use RedisAdapter
 */
final class RedisClient
{
    private ?Redis $conn = null;
    private RedisPool $pool;

    // private readonly RedisPool $pool;

    public function __construct()
    {
        $this->pool = new RedisPool();
    }

    public function getConnection(): void
    {
        $this->conn = $this->pool->getConn();
    }

    /**
     * Put connection back to the pool in order to be reused
     **/
    public function putConnection(): void
    {
        $this->pool->putConn($this->conn);
    }

    public function hGet($key, $hashKey): string|bool
    {
        return $this->conn->hGet($key, $hashKey);
    }

    public function hGetAll($key): array
    {
        return $this->conn->hGetAll($key);
    }

    public function hSet($key, $hashKey, $value): int|bool
    {
        return $this->conn->hSet($key, $hashKey, $value);
    }

    public function hExists($key, $hashKey): mixed
    {
        return $this->conn->hExists($key, $hashKey);
    }

    public function exists($key): int|bool
    {
        return $this->conn->exists($key);
    }

    public function get($key): mixed
    {
        return $this->conn->get($key);
    }

    public function set($key, $value, $timeout = null): mixed
    {
        return $this->conn->set($key, $value, $timeout);
    }

    public function del($key, ...$otherKeys): mixed
    {
        return $this->conn->del($key, ...$otherKeys);
    }

    public function expire($key, $ttl): bool
    {
        return $this->conn->expire($key, $ttl);
    }

    /**
     * @deprecated
     */
    public function raw(string $method, ...$params): mixed
    {
        return $this->rawCommand($method, ...$params);
    }

    public function rawCommand(string $command, ...$arguments): mixed
    {
        return $this->conn->rawCommand(
            $command,
            ...$arguments
        );
    }

    /**
     * @param string $key
     * @param string $payload '[{"attr1": "foo", "attr2": "bar"},{"attr1": "zip", "attr2": "bar"}]'
     * @return mixed
     */
    public function jsonSet(string $key, string $payload): mixed
    {
        return $this->conn->rawCommand(
            'JSON.SET',
            $key,
            '$',
            $payload
        );
    }

    public function jsonSetRaw(string $key, string $payload, string $path): mixed
    {
        return $this->conn->rawCommand(
            'JSON.SET',
            $key,
            $path,
            $payload
        );
    }

    /**
     * @param string $key
     * @param array<attribute,operator,value> $conditions
     *
     * @desc Build from variadic a string like: '$..[?(@.attr1=="foo" && @.attr2=="bar")]'
     *      Possible Operators: ==, >, <, >=, <=, !=
     *
     * @example -> jsonGet('keyName', ['attr1', '==', 'foo'], ['attr2', '==', 'bar']);
     *
     * @return array
     */
    public function jsonGet(string $key, array ...$conditions): array|null
    {
        if (count($conditions) > 0) {

            $conds = '$..[?(';

            foreach ($conditions as $item) {
                $conds .= sprintf('@.%s%s"%s" && ', $item[0],  $item[1], $item[2]);
            }

            $conds = rtrim($conds, " && ") . ')]';

            $res = $this->conn->rawCommand(
                'JSON.GET',
                $key,
                $conds
            );

            return is_string($res) ? json_decode($res, true) : null;
        } else {
            $res = $this->conn->rawCommand(
                'JSON.GET',
                $key
            );

            return is_string($res) ? json_decode($res, true) : null;
        }
    }

    /**
     * @param string $key
     * @param array<attribute,operator,value> $conditions
     *
     * @desc Build from variadic a string like: '$..[?(@.attr1=="foo" || @.attr2=="bar")]'
     *      Possible Operators: ==, >, <, >=, <=, !=
     *
     * @example -> jsonGet('keyName', ['attr1', '==', 'foo'], ['attr2', '==', 'bar']);
     *
     * @return array
     */
    public function jsonGetOr(string $key, array ...$conditions): array|null
    {
        if (count($conditions) > 0) {

            $conds = '$..[?(';

            foreach ($conditions as $item) {
                $conds .= sprintf('@.%s%s"%s" || ', $item[0],  $item[1], $item[2]);
            }

            $conds = rtrim($conds, " || ") . ')]';

            $res = $this->conn->rawCommand(
                'JSON.GET',
                $key,
                $conds
            );

            return is_string($res) ? json_decode($res, true) : null;
        } else {
            $res = $this->conn->rawCommand(
                'JSON.GET',
                $key
            );

            return is_string($res) ? json_decode($res, true) : null;
        }
    }

    public function jsonGetRaw(string $key, string $path): array|null
    {
        $res = $this->conn->rawCommand(
            'JSON.GET',
            $key,
            $path
        );

        return is_string($res) ? json_decode($res, true) : null;
    }

    public function initTransaction(): Redis
    {
        return $this->conn->multi();
    }

    public function endTransaction()
    {
        return $this->conn->exec();
    }
}
