<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Stream;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Extensions\Stream\Values\ConsumerInfo;
use Onion\Framework\Promise\Interfaces\PromiseInterface;

class Group
{
    public function __construct(
        private readonly string $stream,
        private readonly string $name,
        private readonly Client $client,
    ) {
    }

    public function create(
        string | int $id = '$',
        int $readCount = null,
    ): PromiseInterface {
        return $this->client->raw(
            'XGROUP',
            'CREATE',
            $this->stream,
            $this->name,
            $id,
            ...(isset($readCount) ? ['ENTRIESREAD', $readCount] : [])
        );
    }

    public function destroy(): PromiseInterface
    {
        return $this->client->raw(
            'XGROUP',
            'DESTROY',
            $this->stream,
            $this->name,
        );
    }

    public function ack(string | int $id, string | int ...$ids)
    {
        return $this->client->raw(
            'XACK',
            $this->stream,
            $this->name,
            $id,
            ...$ids,
        );
    }

    public function id(string | int $id = '$', int | string $entryRead = null)
    {
        return $this->client->raw(
            'XGROUP',
            'SETID',
            $this->stream,
            $this->name,
            $id,
            ...(isset($entryRead) ? ['ENTRIESREAD', $entryRead] : [])
        );
    }

    public function info(): PromiseInterface
    {
        return $this->client->raw(
            'XINFO',
            'CONSUMERS',
            $this->stream,
            $this->name,
        )->then(fn ($reply) => new ConsumerInfo($reply));
    }

    public function consumer(string $name, int $minIdleTime = 10)
    {
        return new Consumer(
            $this->stream,
            $this->name,
            $name,
            $minIdleTime,
            $this->client,
        );
    }
}
