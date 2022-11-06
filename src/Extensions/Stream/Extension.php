<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Stream;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Interfaces\ExtensionInterface;

class Extension implements ExtensionInterface
{
    public function getName(): string
    {
        return 'stream';
    }

    public function create(Client $client, mixed ...$arguments): object
    {
        return new Stream($client, ...$arguments);
    }
}
