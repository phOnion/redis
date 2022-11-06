<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Cluster;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Interfaces\ExtensionInterface;

class Extension implements ExtensionInterface
{
    private readonly Cluster $instance;

    public function getName(): string
    {
        return 'cluster';
    }

    public function create(Client $client, mixed ...$arguments): Cluster
    {
        return $this->instance ??= new Cluster($client);
    }
}
