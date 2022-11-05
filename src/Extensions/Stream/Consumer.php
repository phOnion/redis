<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Stream;

use DateTimeInterface;
use Onion\Framework\Redis\Client;
use Onion\Framework\Promise\Interfaces\PromiseInterface;

class Consumer
{
    public function __construct(
        private readonly string $stream,
        private readonly string $group,
        private readonly string $name,
        private readonly int $idle,
        private readonly Client $client,
    ) {
    }

    public function create(): PromiseInterface
    {
        return $this->client->raw(
            'XGROUP',
            'CREATECONSUMER',
            $this->stream,
            $this->group,
            $this->name,
        )->then(fn ($state) => (bool) $state);
    }

    public function destroy()
    {
        return $this->client->raw(
            'XGROUP',
            'DELCONSUMER',
            $this->stream,
            $this->group,
            $this->name,
        );
    }

    public function claim(
        DateTimeInterface | int $idle,
        int | string $id,
        int | string ...$ids,
    ): PromiseInterface {
        return $this->client->raw(
            'XCLAIM',
            $this->stream,
            $this->group,
            $this->name,
            $this->idle,
            $id,
            ...$ids,
            ...($idle instanceof DateTimeInterface ? ['TIME', $idle->getTimestamp() * 1000] : ['IDLE', $idle * 1000]),
        );
    }



    public function auto(
        int | string $start,
        int $count = 100,
    ): PromiseInterface {
        return $this->client->raw(
            'XAUTOCLAIM',
            $this->stream,
            $this->group,
            $this->name,
            $this->idle,
            $start,
            'COUNT',
            $count,
        );
    }

    public function pending(int $count = 1): PromiseInterface
    {
        return $this->client->raw(
            'XPENDING',
            $this->stream,
            $this->group,
            'IDLE',
            $this->idle,
            '-',
            '+',
            $count,
            $this->name,
        );
    }

    public function read(
        string | int $id,
        int $count = null,
        int $timeout = null,
    ): PromiseInterface {
        return $this->client->raw(
            'XREADGROUP',
            'GROUP',
            $this->group,
            $this->name,
            ...(isset($count) ? ['COUNT', $count] : []),
            ...(isset($timeout) ? ['BLOCK', $timeout * 1000] : []),
            ...['STREAMS', $this->stream, $id]
        );
    }
}
