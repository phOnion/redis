<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\OrderedSet;

use Onion\Framework\Promise\Interfaces\PromiseInterface;
use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Extensions\OrderedSet\Types\Aggregate;
use Onion\Framework\Redis\Extensions\OrderedSet\Types\Range;

class Store
{
    public function __construct(
        private readonly Client $client,
        private readonly string $name,
    ) {
    }

    public function diff(
        string $destination,
        mixed $name,
        mixed ...$names,
    ): PromiseInterface {
        return $this->client->raw(
            'ZDIFFSTORE',
            $destination,
            2 + count($names),
            $this->name,
            $name,
            ...$names,
        );
    }

    public function intersect(
        string $destination,
        string $name,
        string ...$names,
    ): PromiseInterface {
        return $this->client->raw(
            'ZINTERSTORE',
            $destination,
            2 + count($names),
            $name,
            ...$names,
        );
    }

    public function range(
        string $destination,
        int | string $min,
        int | string $max,
        Range $range = Range::SCORE,
        bool $reverse = false,
    ): PromiseInterface {
        return $this->client->raw(
            'ZRANGESTORE',
            $destination,
            $this->name,
            $min,
            $max,
            $range->value,
            $reverse ? 'REV' : '',
        );
    }

    public function union(
        Aggregate $aggregate,
        string $destination,
        string $name,
        string ...$names
    ): PromiseInterface {
        return $this->client->raw(
            'ZUNIONSTORE',
            $destination,
            2 + count($names),
            $this->name,
            $name,
            'AGGREGATE',
            $aggregate->value,
        );
    }
}
