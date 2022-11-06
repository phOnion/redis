<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Hash;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Interfaces\ExtensionInterface;

class Extension implements ExtensionInterface
{
    public function getName(): string
    {
        return 'hash';
    }

    public function create(Client $client, mixed ...$arguments): Hash
    {
        return new Hash($client, ...$arguments);
    }
}
