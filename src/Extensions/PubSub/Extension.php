<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\PubSub;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Interfaces\ExtensionInterface;

class Extension implements ExtensionInterface
{
    private readonly PubSub $instance;

    public function getName(): string
    {
        return 'pubsub';
    }

    public function create(Client $client, array ...$arguments): PubSub
    {
        return $this->instance ??= new PubSub($client);
    }
}
