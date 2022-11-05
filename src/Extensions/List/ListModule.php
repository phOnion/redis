<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\List;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Extensions\List\Types\{Position, Direction};
use Onion\Framework\Promise\Interfaces\PromiseInterface;
use InvalidArgumentException;

use function Onion\Framework\Promise\await;

class ListModule
{
    public function __construct(
        private readonly Client $client,
        private readonly string $name,
    ) {
    }

    public function index(int $index): PromiseInterface
    {
        return $this->client->raw('LINDEX', $this->name, $index);
    }

    public function get(int $index): PromiseInterface
    {
        return $this->client->raw('LGET', $this->name, $index);
    }

    public function insert(
        mixed $pivot,
        mixed $value,
        Position $direction = Position::BEFORE
    ): PromiseInterface {
        return $this->client->raw(
            'LINSERT',
            $this->name,
            $direction->value,
            $pivot,
            $value,
        );
    }

    public function length(): PromiseInterface
    {
        return $this->client->raw('LLEN', $this->name);
    }

    public function move(
        string $destination,
        Direction $from,
        Direction $to,
    ): PromiseInterface {
        return $this->client->raw(
            'LMOVE',
            $this->name,
            $destination,
            $from->value,
            $to->value,
        );
    }

    public function find(
        mixed $value,
        int $rank = null,
        int $count = null,
        int $length = null,
    ): PromiseInterface {
        return $this->client->raw(
            'LPOS',
            $this->name,
            $value,
            match ($rank) {
                null => [],
                default => ['RANK', $rank],
            },
            match ($count) {
                null => [],
                default => ['COUNT', $count],
            },
            match ($length) {
                null => [],
                default => ['MAXLEN', $length]
            }
        );
    }

    public function shift(int $count = 1): PromiseInterface
    {
        return $this->client->raw('LPOP', $this->name, $count);
    }

    public function pop(int $count = 1): PromiseInterface
    {
        return $this->client->raw('RPOP', $this->name, $count);
    }

    public function unshift(mixed $value, ...$values): PromiseInterface
    {
        return $this->client->raw('LPUSH', $this->name, $value, ...$values);
    }

    public function push(mixed $value, ...$values): PromiseInterface
    {
        return $this->client->raw('RPUSH', $this->name, $value, ...$values);
    }

    public function range(int $from, int $to): PromiseInterface
    {
        return $this->client->raw('LRANGE', $this->name, $from, $to);
    }

    public function rem(int $count, mixed $value): PromiseInterface
    {
        return $this->client->raw('LREM', $this->name, $count, $value);
    }

    public function set(int $index, mixed $value): PromiseInterface
    {
        return $this->client->raw('LSET', $this->name, $index, $value);
    }

    public function trim(int $from, int $to): PromiseInterface
    {
        return $this->client->raw('LTRIM', $this->name, $from, $to);
    }

    public function blocking(): Blocking
    {
        return new Blocking($this->client, $this->name);
    }

    public function count(): int
    {
        return await($this->length());
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_int($offset)) {
            throw new InvalidArgumentException(
                "Redis list keys can be only integers"
            );
        }

        await($this->set($offset, $value));
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!is_int($offset)) {
            throw new InvalidArgumentException(
                "Redis list keys can be only integers"
            );
        }

        return await($this->get($offset));
    }

    public function offsetExists(mixed $offset): bool
    {
        if (!is_int($offset)) {
            throw new InvalidArgumentException(
                "Redis list keys can be only integers"
            );
        }

        return await($this->range($offset, $offset + 1)) !== [];
    }

    public function offsetUnset(mixed $offset): void
    {
        if (!is_int($offset)) {
            throw new InvalidArgumentException(
                "Redis list keys can be only integers"
            );
        }

        await(
            $this->set($offset, '@DEL@')
                ->then(fn () => $this->rem(1, '@DEL@'))
        );
    }
}
