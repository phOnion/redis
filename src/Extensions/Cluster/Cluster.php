<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Cluster;

use InvalidArgumentException;
use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Extensions\Cluster\Types\{Failover, Reset, Slot};
use Onion\Framework\Promise\Interfaces\PromiseInterface;
use Onion\Framework\Promise\Promise;

use function Onion\Framework\Promise\await;

class Cluster
{
    private array $connections = [];

    public function __construct(
        private readonly Client $client,
    ) {
        $this->connections[$client->getClusterKey()] = $client;
    }

    public function register(Client $client, int $bus = null): PromiseInterface
    {
        if (!isset($this->connections[$client->getClusterKey()])) {
            $this->connections[$client->getClusterKey()] = $client;
            await($client->cluster()->register($this->client));

            foreach ($this->connections as $node) {
                await($client->cluster()->register($node));
            }

            return $this->client->raw("CLUSTER MEET {$client->getClusterKey()} {$bus}");
        }

        return Promise::resolve(true);
    }

    public function send(string $key, array $command): PromiseInterface
    {
        if (!isset($this->connections[$key])) {
            throw new InvalidArgumentException("Cluster node '{$key}' unknown in '{$this->client->getClusterKey()}'");
        }

        return $this->connections[$key]->raw($command);
    }

    public function add(int $slot, int ...$slots): PromiseInterface
    {
        return $this->client->raw(
            'CLUSTER',
            'ADDSLOTS',
            $slot,
            ...$slots
        );
    }

    public function bump(): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'BUMPEPOCH');
    }

    public function failures(int $node): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'COUNT-FAILURE-REPORTS', $node);
    }

    public function count(int $slot): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'COUNTKEYSINSLOT', $slot);
    }

    public function delete(int $slot, int ...$slots): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'DELSLOTS', $slot, ...$slots);
    }

    public function failover(Failover $kind = null): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'FAILOVER', $kind?->value);
    }

    public function flush(): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'FLUSHSLOTS');
    }

    public function forget(int $node): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'FORGET', $node);
    }

    public function keys(int $slot, int $count): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'GETKEYSINSLOT', $slot, $count);
    }

    public function info(): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'INFO');
    }

    public function slot(string $key): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'KEYSLOT', $key);
    }

    public function links(): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'LINKS');
    }

    public function meet(string $ip, int $port, int $bus = null): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'MEET', $ip, $port, ($bus ? $this->serializer->serialize($bus) : null));
    }

    public function id(): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'MYID');
    }

    public function nodes(): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'NODES');
    }

    public function replicas(int $node): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'REPLICAS', $node);
    }

    public function replicate(int $node): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'REPLICATE', $node);
    }

    public function reset(Reset $kind = null): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'RESET', $kind?->value);
    }

    public function save(): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'SAVECONFIG');
    }

    public function epoch(int | string $epoch): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'SET-CONFIG-EPOCH', $epoch);
    }

    public function set(Slot $kind, int | string | null $slot): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'SETSLOT', ...match ($kind) {
            Slot::STABLE => [$kind->value],
            default => [$kind->value, $slot],
        });
    }

    public function shards(): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'SHARDS');
    }

    public function slots(): PromiseInterface
    {
        return $this->client->raw('CLUSTER', 'SLOTS');
    }

    public function readonly(bool $state): PromiseInterface
    {
        return $this->client->raw(($state ? 'READONLY' : 'READWRITE'));
    }
}
