<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Basic;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Interfaces\ExtensionInterface;

class Extension implements ExtensionInterface
{
    private readonly Basic $instance;

    public function getName(): string
    {
        return 'basic';
    }

    public function create(
        Client $client,
        mixed ...$arguments
    ): Basic {
        return $this->instance ??= new Basic($client);
    }
}
