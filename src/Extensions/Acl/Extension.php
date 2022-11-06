<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Acl;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Extensions\Acl\Acl;
use Onion\Framework\Redis\Interfaces\ExtensionInterface;

class Extension implements ExtensionInterface
{
    public function getName(): string
    {
        return 'acl';
    }

    public function create(Client $client, mixed ...$arguments): Acl
    {
        return new Acl($client);
    }
}
