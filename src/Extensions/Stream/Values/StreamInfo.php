<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Stream\Values;

class StreamInfo
{
    private readonly array $reply;
    private readonly int $length;
    private readonly int $radixTreeKeys;
    private readonly int $radixTreeNodes;
    private readonly string $lastGeneratedId;

    private array $entries = [];
    private array $groups = [];
    private array $groupPending = [];
    private array $groupConsumers = [];

    private array $groupConsumersPending = [];

    public function __construct(array $reply)
    {
        $this->reply = $this->process($reply);

        $this->length = $this->reply['length'];
        $this->radixTreeKeys = $this->reply['radix-tree-keys'];
        $this->radixTreeNodes = $this->reply['radix-tree-nodes'];
        $this->lastGeneratedId = $this->reply['last-generated-id'];

        foreach ($this->reply['entries'] as [$id, $data]) {
            $this->entries[$id] = $this->process($data);
        }

        foreach ($this->reply['groups'] as $group) {
            $this->groups[$group['name']] = [
                'last-delivered-id' => $group['last-delivered-id'],
                'pel-count' => $group['pel-count'],
            ];

            foreach ($group['pending'] ?? [] as $pending) {
                $this->groupPending[$group['name']][] = [
                    'id' => $pending[0],
                    'consumer' => $pending[1],
                    'idle-since' => $pending[2],
                    'retries' => $pending[3],
                ];
            }

            foreach ($group['consumers'] ?? [] as $consumer) {
                $this->groupConsumers[$consumer['name']] = [
                    'last-seen' => $consumer['seen-time'],
                    'pel-count' => $consumer['pel-count'],
                ];

                foreach ($consumer['pending'] as [$id, $idleSince, $retry]) {
                    $this->groupConsumersPending[$consumer['name']][] = [
                        'id' => $id,
                        'idle-since' => $idleSince,
                        'retries' => $retry,
                    ];
                }
            }
        }
    }

    private function process(array $reply): array
    {
        $results = [];
        for ($i = 0; $i < count($reply); $i += 2) {
            if (is_array($reply[$i])) {
                var_dump($reply);
            }

            $results[$reply[$i]] = match ($reply[$i]) {
                'groups' => array_map($this->process(...), $reply[$i + 1]),
                'consumers' => array_map($this->process(...), $reply[$i + 1]),
                default => $reply[$i + 1] ?? null
            };
        }

        return $results;
    }

    public function length(): int
    {
        return $this->length;
    }

    public function radixTreeKeys(): int
    {
        return $this->radixTreeKeys;
    }

    public function radixTreeNodes(): int
    {
        return $this->radixTreeNodes;
    }

    public function lastGeneratedId(): string | int
    {
        return $this->lastGeneratedId;
    }

    public function groups(): array
    {
        return array_keys($this->groups);
    }

    public function group(string $name): array
    {
        if (!isset($this->groups[$name])) {
            return [];
        }

        return [...$this->groups[$name], 'pending' => ($this->groupPending[$name] ?? [])];
    }

    public function consumers(): array
    {
        return array_keys($this->groupConsumers);
    }

    public function consumer(string $name): array
    {
        if (!isset($this->groupConsumers[$name])) {
            return [];
        }

        return [...$this->groupConsumers[$name], 'pending' => $this->groupConsumersPending[$name] ?? []];
    }
}
