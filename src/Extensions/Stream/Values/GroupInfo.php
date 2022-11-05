<?php

namespace Onion\Framework\Redis\Extensions\Stream\Values;

use Onion\Framework\Redis\Utils;

class GroupInfo
{
    private array $groups = [];

    public function __construct(array $reply)
    {
        $items = Utils::normalizeArray($reply);

        foreach ($reply as $row) {
            $items = Utils::normalizeArray($row);

            var_dump($items);
            exit;

            $this->groups[$items['name']] = [
                'consumers' => $items['consumers'],
                'pending' => $items['pending'],
                'lastId' => $items['last-delivered-id'],
                'entriesRead' => $items['entries-read'],
                'lag' => $items['lag'],
            ];
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function consumers(): int
    {
        return $this->consumers;
    }

    public function pending(): int
    {
        return $this->pending;
    }

    public function lastDeliveredId(): string
    {
        return $this->lastId;
    }

    public function entriesRead(): int
    {
        return $this->entriesRead;
    }

    public function lag(): int
    {
        return $this->lag;
    }
}
