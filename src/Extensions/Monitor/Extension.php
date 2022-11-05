<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Monitor;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Interfaces\ExtensionInterface;

class Extension implements ExtensionInterface
{
    private readonly Monitor $instance;

    public function getName(): string
    {
        return 'monitoring';
    }

    public function create(Client $client, mixed ...$arguments): Monitor
    {
        return $this->instance ??= new Monitor($client);
    }
}
