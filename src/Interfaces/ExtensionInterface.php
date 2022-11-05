<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Interfaces;

use Closure;
use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Serializer;

interface ExtensionInterface
{
    public function getName(): string;
    public function create(
        Client $client,
        mixed ...$arguments
    ): object;
}
