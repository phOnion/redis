<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Geo\Types;

enum Units: string
{
    case METERS = 'm';
    case KILOMETERS = 'km';
    case MILES = 'mi';
    case FEET = 'ft';
}
