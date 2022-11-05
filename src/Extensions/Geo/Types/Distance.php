<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Geo\Types;

class Distance
{
    public function __construct(
        private readonly int | float | string $distance,
        private readonly Units $units,
    ) {
    }

    public function distance(): int | float | string
    {
        return $this->distance;
    }

    public function unit(): string
    {
        return $this->units->value;
    }
}
