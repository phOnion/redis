<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\List;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Interfaces\ExtensionInterface;

class Extension implements ExtensionInterface
{
    public function getName(): string
    {
        return 'list';
    }

    public function create(Client $client, mixed ...$arguments): ListModule
    {
        return new ListModule($client, ...$arguments);
    }
}
