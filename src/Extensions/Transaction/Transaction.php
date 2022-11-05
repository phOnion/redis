<?php

namespace Onion\Framework\Redis\Extensions\Transaction;

use Onion\Framework\Redis\Client;
use Onion\Framework\Promise\Interfaces\PromiseInterface;

class Transaction
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function begin(): PromiseInterface
    {
        return $this->client->raw('MULTI');
    }

    public function commit(): PromiseInterface
    {

        return $this->client->raw('COMMIT');
    }

    public function rollback(): PromiseInterface
    {
        return $this->client->raw('DISCARD');
    }

    public function watch(string $key, string ...$keys): PromiseInterface
    {
        return $this->client
            ->raw('WATCH', $key, ...$keys);
    }

    public function unwatch(): PromiseInterface
    {
        return $this->client->raw('UNWATCH');
    }
}
