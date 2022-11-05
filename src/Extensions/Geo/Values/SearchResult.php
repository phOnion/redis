<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Geo\Values;

class SearchResult
{
    private readonly Coordinates $coordinates;

    public function __construct(
        private readonly string $name,
        private readonly string $distance,
        private readonly int $hash,
        array $coordinates,
    ) {
        $this->coordinates = new Coordinates(...$coordinates);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function coordinate(): Coordinates
    {
        return $this->coordinates;
    }

    public function distance(): string
    {
        return $this->distance;
    }

    public function hash(): int
    {
        return $this->hash;
    }
}
