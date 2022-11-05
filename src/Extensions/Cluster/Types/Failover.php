<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Cluster\Types;

enum Failover: string
{
    case FORCE = 'FORCE';
    case TAKEOVER = 'TAKEOVER';
}
