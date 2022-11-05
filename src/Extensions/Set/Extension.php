<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Set;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Interfaces\ExtensionInterface;

class Extension implements ExtensionInterface
{
    public function getName(): string
    {
        return 'set';
    }

    public function create(Client $client, array ...$arguments): Set
    {
        return new Set($client, ...$arguments);
    }
}
