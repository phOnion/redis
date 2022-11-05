<?php

namespace Onion\Framework\Redis\Extensions\Basic;

use Onion\Framework\Redis\Client;
use DateTimeInterface;
use Onion\Framework\Promise\Interfaces\PromiseInterface;

class Basic
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function set(
        string $key,
        mixed $value,
        DateTimeInterface | int $ttl = null,
        string $type = null // 'XX', 'NX'
    ): PromiseInterface {
        return $this->client->raw(
            'SET',
            $key,
            $value,
            $type,
            'GET',
            ...match (true) {
                null === $ttl => ['KEEPTTL'],
                $ttl instanceof DateTimeInterface => ['EXAT', $ttl->getTimestamp()],
                is_int($ttl) => ['EX', $ttl],
            }
        );
    }

    public function persist(string $key): PromiseInterface
    {
        return $this->client->raw('PERSIST', $key);
    }

    public function type(string $key): PromiseInterface
    {
        return $this->client->raw('TYPE', $key);
    }

    public function get(
        string $key
    ): PromiseInterface {
        return $this->client->raw('GET', $key);
    }

    public function keys(
        string $pattern = '*'
    ): PromiseInterface {
        return $this->client->raw('KEYS',  $pattern);
    }

    public function delete(string $key, string ...$keys): PromiseInterface
    {
        return $this->client->raw('DEL', $key, ...$keys);
    }

    public function unlink(string $key, string ...$keys): PromiseInterface
    {
        return $this->client->raw('unlink', $key, ...$keys);
    }

    public function copy(string $source, string $destination, bool $replace = true)
    {
        return $this->client->raw('COPY', $source, $destination, ($replace ? ' REPLACE' : ''));
    }

    public function rename(string $old, string $new)
    {
        return $this->client->raw('RENAME', $old, $new);
    }

    public function size(): PromiseInterface
    {
        return $this->client->raw('DBSIZE');
    }

    public function flush(): PromiseInterface
    {
        return $this->client->raw('FLUSHALL', 'ASYNC');
    }
}
