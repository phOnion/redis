<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Stream\Values;

use Onion\Framework\Redis\Utils;

class ConsumerInfo
{
    private array $consumers = [];

    public function __construct(array $reply)
    {
        foreach ($reply as $row) {
            $consumer = Utils::normalizeArray($row);

            $this->consumers[$consumer['name']] = [
                'pending' => $consumer['pending'],
                'idle-since' => round(($consumer['idle'] ?? $consumer['idle-since']) / 1000),
            ];
        }
    }

    public function consumers(): array
    {
        return array_keys($this->consumers);
    }

    public function consumer(string $name): ?array
    {
        return $this->consumers[$name] ?? null;
    }
}
