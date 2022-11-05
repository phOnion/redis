<?php

namespace Onion\Framework\Redis\Extensions\List;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Extensions\List\Types\Direction;
use Onion\Framework\Promise\Interfaces\PromiseInterface;

class Blocking
{
    public function __construct(
        private readonly Client $client,
        private readonly string $name,
    ) {
    }

    public function move(string $destination, Direction $from, Direction $to, float $timeout = 0.0): PromiseInterface
    {
        return $this->client->raw(
            'BLMOVE',
            $this->name,
            $destination,
            $from->value,
            $to->value,
            $timeout,
        );
    }

    public function shift(float $timeout = 0.0): PromiseInterface
    {
        return $this->client->raw(
            'BLPOP',
            $this->name,
            $timeout,
        );
    }

    public function pop(float $timeout = 0.0): PromiseInterface
    {
        return $this->client->raw(
            'BRPOP',
            $this->name,
            $timeout,
        );
    }
}
