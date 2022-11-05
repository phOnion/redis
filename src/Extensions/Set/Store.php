<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Set;

use Onion\Framework\Redis\Client;
use Onion\Framework\Promise\Interfaces\PromiseInterface;

class Store
{
    public function __construct(
        private readonly string $name,
        private readonly Client $client,
    ) {
    }

    public function diff(string $destination, string $set, string ...$sets): PromiseInterface
    {
        return $this->client->raw(
            'DIFFSTORE',
            $destination,
            $this->name,
            $set,
            ...$sets,
        );
    }

    public function intersect(string $destination, string $set, string ...$sets): PromiseInterface
    {
        return $this->client->raw(
            'SINTERSTORE',
            $destination,
            $this->name,
            $set,
            ...$sets
        );
    }

    public function union(string $destination, string $set, string ...$sets): PromiseInterface
    {
        return $this->client->raw(
            'SUNIONSTORE',
            $destination,
            $set,
            ...$sets
        );
    }
}
