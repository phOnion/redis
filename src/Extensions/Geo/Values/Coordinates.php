<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Geo\Values;

class Coordinates
{
    public function __construct(
        private readonly string | float $longitude,
        private readonly string | float $latitude,

    ) {
    }

    public function longitude(): string | float
    {
        return $this->longitude;
    }

    public function lon(): string | float
    {
        return $this->longitude;
    }

    public function latitude(): string | float
    {
        return $this->latitude;
    }

    public function lat(): string | float
    {
        return $this->latitude;
    }
}
