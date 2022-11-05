<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Cluster\Types;

enum Slot: string
{
    case IMPORTING = 'IMPORTING';
    case MIGRATING = 'MIGRATING';
    case NODE = 'NODE';
    case STABLE = 'STABLE';
}
