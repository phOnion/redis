<?php

namespace Onion\Framework\Redis\Extensions\Hash;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Onion\Framework\Redis\Client;
use Onion\Framework\Promise\Interfaces\PromiseInterface;

use function Onion\Framework\generator;
use function Onion\Framework\Promise\await;

class Hash implements Countable, ArrayAccess
{
    public function __construct(
        private readonly Client $client,
        private readonly string $name,
    ) {
    }

    public function set(array $values): PromiseInterface
    {
        $pairs = [];
        foreach ($values as $key => $value) {
            array_push($pairs, $key, $value);
        }

        return $this->client->raw('HSET', $this->name, ...$pairs);
    }

    public function get(string $field): PromiseInterface
    {
        return $this->client->raw('HGET', $this->name, $field);
    }

    public function del(string $field): PromiseInterface
    {
        return $this->client->raw('HDEL', $this->name, $field);
    }

    public function exists(string $field): PromiseInterface
    {
        return $this->client->raw('HEXISTS', $this->name, $field);
    }

    public function all(): PromiseInterface
    {
        return $this->client->raw('HGETALL', $this->name);
    }

    public function increment(string $field, int $by = 1): PromiseInterface
    {
        return $this->client->raw('HINCRBY', $this->name, $field, $by);
    }

    public function keys(): PromiseInterface
    {
        return $this->client->raw('HKEYS', $this->name);
    }

    public function len(): PromiseInterface
    {
        return $this->client->raw('HLEN', $this->name);
    }

    public function mget(string ...$fields): PromiseInterface
    {
        return $this->client->raw('HMGET', ...$fields);
    }

    public function scan(
        string $pattern = null,
        int $count = null,
        string $type = null
    ): iterable {
        $extra = [];
        if ($pattern !== null) {
            array_push($extra, 'MAATCH', $pattern);
        }

        if ($count !== null) {
            array_push($extra, 'COUNT', $count);
        }

        if ($type !== null) {
            array_push($extra, 'TYPE', $type);
        }

        return generator(function () use ($extra) {
            $cursor = 0;
            do {
                [$cursor, $items] = $this->client->raw('HSCAN', $this->name, $cursor, ...$extra);

                yield from $items;
            } while (((int) $cursor) !== 0);
        });
    }

    public function strlen(string $field): PromiseInterface
    {
        return $this->client->raw('HSTRLEN', $this->name, $field);
    }

    public function values(): PromiseInterface
    {
        return $this->client->raw('HVALS', $this->name);
    }

    public function count(): int
    {
        return await($this->len());
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!is_string($offset)) {
            throw new InvalidArgumentException(
                "Redis hash keys can be only strings"
            );
        }

        return await($this->get($offset));
    }

    public function offsetExists(mixed $offset): bool
    {
        if (!is_string($offset)) {
            throw new InvalidArgumentException(
                "Redis hash keys can be only strings"
            );
        }

        return await($this->exists($offset));
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($offset)) {
            throw new InvalidArgumentException(
                "Redis hash keys can be only strings"
            );
        }

        await($this->set([$offset => $value]));
    }

    public function offsetUnset(mixed $offset): void
    {
        if (!is_string($offset)) {
            throw new InvalidArgumentException(
                "Redis hash keys can be only strings"
            );
        }

        await($this->del($offset));
    }
}
