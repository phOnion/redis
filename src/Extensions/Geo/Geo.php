<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Geo;

use Onion\Framework\Redis\Client;
use Onion\Framework\Redis\Extensions\Geo\Types\Distance;
use Onion\Framework\Redis\Extensions\Geo\Types\Order;
use Onion\Framework\Redis\Extensions\Geo\Types\Units;
use Onion\Framework\Redis\Extensions\Geo\Values\Coordinates;
use Onion\Framework\Promise\Interfaces\PromiseInterface;
use Onion\Framework\Redis\Commands\Geo\Values\SearchResult;

class Geo
{
    public function __construct(
        private readonly Client $client,
        private readonly string $name,
    ) {
    }

    public function add(string $name, Coordinates $coordinates): PromiseInterface
    {
        return $this->client->raw(
            'GEOADD',
            $this->name,
            $coordinates->lon(),
            $coordinates->lat(),
            $name,
        );
    }

    public function distance(string $source, string $destination, Units $unit = Units::METERS): PromiseInterface
    {
        return $this->client->raw(
            'GEODIST',
            $this->name,
            $source,
            $destination,
            $unit->value,
        );
    }

    public function hash(string $name, string ...$names): PromiseInterface
    {
        return $this->client->raw(
            'GEOHASH',
            $this->name,
            $name,
            ...$names
        );
    }

    public function position(string $name, string ...$names): PromiseInterface
    {
        return $this->client->raw(
            'GEOPOS',
            $this->name,
            $name,
            ...$names,
        )->then(
            fn ($reply) => array_map(
                fn (array $coordinates) => new Coordinates(...$coordinates),
                $reply
            )
        );
    }

    public function search(
        string | array $position,
        Distance $radius,
        Order $sort = Order::ASC,
    ): PromiseInterface {
        $source = '';
        if (is_array($position)) {
            [$longitude, $latitude] = $position;

            $source = ['FROMLONLAT',  $longitude, $latitude];
        } else {
            $source = ['FROMMEMBER', $position];
        }

        $source = [
            ...$source,
            'BYRADIUS',
            $radius->distance(),
            $radius->unit(),
            $sort->name,
            'WITHCOORD',
            'WITHDIST',
            'WITHHASH'
        ];

        return $this->client->raw('GEOSEARCH', $this->name, ...$source)
            ->then(fn (array $reply) => array_map(fn ($item) => new SearchResult(...$item), $reply));
    }
}
