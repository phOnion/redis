<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Transaction;

use Onion\Framework\Redis\Client;
use Closure;
use Onion\Framework\Redis\Interfaces\ExtensionInterface;
use Onion\Framework\Redis\Serializer;

class Extension implements ExtensionInterface
{
    private readonly Transaction $instance;

    public function getName(): string
    {
        return 'transaction';
    }

    public function create(Client $client, array ...$arguments): object
    {
        return $this->instance ??= new Transaction($client);
    }
}
