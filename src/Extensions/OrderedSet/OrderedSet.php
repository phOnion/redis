<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\OrderedSet;

use Countable;
use Onion\Framework\Promise\Interfaces\PromiseInterface;
use Onion\Framework\Promise\Promise;
use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Extensions\OrderedSet\Types\{Aggregate, Range};

use function Onion\Framework\generator;
use function Onion\Framework\Promise\await;

class OrderedSet implements Countable
{
    public function __construct(
        private readonly Client $client,
        private readonly string $name,
    ) {
    }

    public function push(int $score, mixed $member, mixed ...$pairs): PromiseInterface
    {
        return $this->client->raw(
            'ZADD',
            $this->name,
            $score,
            $member,
            ...$pairs,
        );
    }

    public function length(): PromiseInterface
    {
        return $this->client->raw(
            'ZCARD',
            $this->name,
        );
    }

    public function diff(string $key, string ...$keys): PromiseInterface
    {
        $keys[] = 'WITHSCORES';

        return $this->client->raw(
            'ZDIFF',
            2 + count($keys),
            $key,
            ...$keys,
        )->then(fn (array $reply) => generator(function () use ($reply) {
            for ($i = 0; $i < count($reply); $i + 2) {
                yield [$reply[$i], $reply[$i + 1]];
            }
        }));
    }

    public function increase(string $value, int $increment = 1): PromiseInterface
    {
        return $this->client->raw(
            'ZINCRBY',
            $this->name,
            $increment,
            $value,
        );
    }

    public function scores(string $key, string ...$keys)
    {
        return $this->client->raw(
            'ZMSCORE',
            $this->name,
            $key,
            ...$keys,
        );
    }

    public function intersect(
        Aggregate $aggregate,
        string $key,
        string ...$keys
    ): PromiseInterface {
        return $this->client->raw(
            'ZINTER',
            $this->name,
            1 + count($keys),
            $key,
            ...$keys,
            ...[
                'AGGREGATE',
                $aggregate->value,
            ]
        );
    }

    public function unshift(int $count = 1): PromiseInterface
    {
        return $this->client->raw(
            'ZPOPMIN',
            $this->name,
            $count,
        );
    }

    public function pop(int $count = 1): PromiseInterface
    {
        return $this->client->raw(
            'ZPOPMAX',
            $this->name,
            $count,
        );
    }

    public function rand(int $count = 1): PromiseInterface
    {
        return $this->client->raw(
            'ZRANDMEMBER',
            $this->name,
            $count,
            'WITHSCORES',
        )->then(fn (array $reply) => generator(function () use ($reply) {
            for ($i = 0; $i < count($reply); $i + 2) {
                yield [$reply[$i], $reply[$i + 1]];
            }
        }));
    }

    public function range(
        int | string $min,
        int | string $max,
        Range $range = Range::SCORE,
        bool $reverse = false,
    ) {
        return $this->client->raw(
            'ZRANGE',
            $this->name,
            $min,
            $max,
            $range->value,
            $reverse ? 'REV' : '',
            'WITHSCORES',
        )->then(fn (array $reply) => generator(function () use ($reply) {
            for ($i = 0; $i < count($reply); $i + 2) {
                yield [$reply[$i], $reply[$i + 1]];
            }
        }));
    }

    public function rank(string $name, bool $reverse = false): PromiseInterface
    {
        return $this->client->raw(
            $reverse ? 'ZREVRANK' : 'ZRANK',
            $this->name,
            $name,
        );
    }

    public function remove(mixed $value, mixed ...$values): PromiseInterface
    {
        return $this->client->raw(
            'ZREM',
            $this->name,
            $value,
            ...$values,
        );
    }

    public function scan(string $pattern = null, int $count = null): PromiseInterface
    {
        $extra = [];
        if (isset($pattern)) {
            array_push($extra, 'MATCH', $pattern);
        }

        if (isset($count)) {
            array_push($extra, 'COUNT', $count);
        }

        return Promise::resolve(generator(function () use ($extra) {
            $cursor = 0;
            do {
                [$cursor, $data] = await($this->client->raw(
                    'ZSCAN',
                    $this->name,
                    $cursor,
                    ...$extra
                ));

                yield from $data;
            } while ($cursor !== 0);
        }));
    }

    public function score(string $value)
    {
        return $this->client->raw(
            'ZSCORE',
            $this->name,
            $value,
        );
    }

    public function union(
        Aggregate $aggregate,
        string $name,
        string ...$names
    ): PromiseInterface {
        array_push($names, 'AGGREGATE', $aggregate->value, 'WITHSCORES');

        return $this->client->raw(
            'ZUNION',
            $this->name,
            $name,
            ...$names
        )->then(fn (array $reply) => generator(function () use ($reply) {
            for ($i = 0; $i < count($reply); $i + 2) {
                yield [$reply[$i], $reply[$i + 1]];
            }
        }));
    }

    public function store(): Store
    {
        return new Store($this->client, $this->name);
    }

    public function count(): int
    {
        return await($this->length());
    }
}
