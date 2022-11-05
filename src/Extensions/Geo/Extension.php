<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Geo;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Interfaces\ExtensionInterface;

class Extension implements ExtensionInterface
{
    public function getName(): string
    {
        return 'geo';
    }

    public function create(Client $client, array ...$arguments): Geo
    {
        return new Geo($client, ...$arguments);
    }
}
