<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\OrderedSet;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Interfaces\ExtensionInterface;

class Extension implements ExtensionInterface
{
    public function getName(): string
    {
        return 'orderedSet';
    }

    public function create(Client $client, array ...$arguments): OrderedSet
    {
        return new OrderedSet($client, ...$arguments);
    }
}
