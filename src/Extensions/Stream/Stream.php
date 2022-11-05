<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Stream;

use Countable;
use Onion\Framework\Promise\Interfaces\PromiseInterface;
use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Extensions\Stream\{
    Group,
    Types\Trim,
    Values\GroupInfo,
    Values\StreamInfo
};

use function Onion\Framework\generator;
use function Onion\Framework\Promise\await;

class Stream implements Countable
{
    public function __construct(
        private readonly Client $client,
        private readonly string $name,
    ) {
    }

    public function info(bool $full = false, int $count = null): PromiseInterface
    {
        $extra = [];
        if ($full) {
            $extra[] = 'FULL';
            if (isset($count)) {
                array_push($extra, 'COUNT', $count);
            }
        }


        return $this->client->raw(
            'XINFO',
            'STREAM',
            $this->name,
            ...$extra,
        )->then(fn (array $reply) => new StreamInfo($reply));
    }

    public function groups(): PromiseInterface
    {
        return $this->client->raw(
            'XINFO',
            'GROUPS',
            $this->name,
        )->then(fn (array $reply) => new GroupInfo($reply));
    }

    public function add(array $fields, string $id = '*'): PromiseInterface
    {
        $pairs = [];
        foreach ($fields as $name => $value) {
            array_push($pairs, $name, $value);
        }

        return $this->client->raw(
            'XADD',
            $this->name,
            $id,
            ...$pairs,
        );
    }

    public function remove(string $id, string ...$ids): PromiseInterface
    {
        return $this->client->raw(
            'XDEL',
            $this->name,
            $id,
            ...$ids
        );
    }

    public function length(): PromiseInterface
    {
        return $this->client->raw(
            'XLEN',
            $this->name,
        );
    }

    public function trim(
        string | int $threshold,
        int $limit = null,
        Trim $kind = TRIM::LENGTH_APPROX,
    ): PromiseInterface {
        return $this->client->raw(
            'XTRIM',
            $this->name,
            $kind->value,
            $threshold,
            $limit,
        );
    }

    public function range(
        string | int $start,
        string | int $end,
        int $count = null,
        bool $reverse = false
    ): PromiseInterface {
        return $this->client->raw(
            $reverse ? 'XREVRANGE' : 'XRANGE',
            $this->name,
            $start,
            $end,
            ...(isset($count) ? ['COUNT', $count] : [])
        );
    }

    public function read(
        array $ids,
        int $count = null,
        int $block = null,
        array $streams = []
    ) {
        $this->client->raw(
            'XREAD',
            ...(isset($count) ? ['COUNT', $count] : []),
            ...(isset($block) ? ['BLOCK', $block] : []),
            ...['STREAMS', $this->name],
            ...$streams,
            ...$ids
        )->then(fn (array $results) => generator(function () use ($results) {
            for ($i = 0; $i < count($results); $i + 2) {
                yield $results[$i] => $results[$i + 1];
            }
        }));
    }

    public function group(string $name)
    {
        return new Group(
            $this->name,
            $name,
            $this->client,
        );
    }

    public function count(): int
    {
        return await($this->length());
    }
}
