<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Set;

use Onion\Framework\Redis\Client;
use Onion\Framework\Promise\Interfaces\PromiseInterface;
use Onion\Framework\Promise\Promise;

use function Onion\Framework\generator;
use function Onion\Framework\Promise\await;

class Set
{
    public function __construct(
        private readonly Client $client,
        private readonly string $name,
    ) {
    }

    public function push(mixed $value, mixed ...$values): PromiseInterface
    {
        return $this->client->raw(
            'SADD',
            $this->name,
            $value,
            ...$values
        );
    }

    public function length(): PromiseInterface
    {
        return $this->client->raw('SCARD', $this->name);
    }

    public function diff(string $set, string ...$sets): PromiseInterface
    {
        return $this->client->raw(
            'SDIFF',
            $this->name,
            $set,
            ...$sets,
        );
    }

    public function intersect(string $set, string ...$sets): PromiseInterface
    {
        return $this->client->raw(
            'SINTER',
            $this->name,
            $set,
            ...$sets,
        );
    }

    public function values(): PromiseInterface
    {
        return $this->client->raw('SMEMBERS', $this->name);
    }

    public function move(string $destination, mixed $value): PromiseInterface
    {
        return $this->client->raw(
            'SMOVE',
            $this->name,
            $destination,
            $value,
        );
    }

    public function pop(int $count = 1): PromiseInterface
    {
        return $this->client->raw(
            'SPOP',
            $this->name,
            $count
        );
    }

    public function rand(int $count = 1): PromiseInterface
    {
        return $this->client->raw(
            'SRANDMEMBER',
            $this->name,
            $count,
        );
    }

    public function contains(mixed $value, mixed ...$values): PromiseInterface
    {
        return $this->client->raw(
            'SISMEMBER',
            $this->name,
            $value,
            ...$values,
        )->then(fn (array $values) => count($values) === array_filter($values));
    }

    public function remove(mixed $value, mixed ...$values): PromiseInterface
    {
        return $this->client->raw(
            'SREM',
            $this->name,
            $value,
            ...$values
        );
    }

    public function scan(
        string $pattern = null,
        int $count = null,
        string $type = null,
    ): PromiseInterface {
        $extra = [];
        if (isset($pattern)) {
            array_push($extra, 'MATCH', $pattern);
        }

        if (isset($count)) {
            array_push($extra, 'COUNT', $count);
        }

        if (isset($type)) {
            array_push($extra, 'TYPE', $type);
        }

        return Promise::resolve(generator(function () use ($extra) {
            $cursor = 0;

            do {
                [$cursor, $data] = await($this->client->raw(
                    'SSCAN',
                    $this->name,
                    $cursor,
                    ...$extra
                ));

                yield from $data;
            } while ($cursor !== 0);
        }));
    }

    public function union(string $set, string ...$sets): PromiseInterface
    {
        return $this->client->raw(
            'SUNION',
            $this->name,
            $set,
            ...$sets,
        );
    }

    public function store()
    {
        return new Store($this->name, $this->client);
    }

    public function count(): int
    {
        return await($this->length());
    }
}
