<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\Cluster\Types;

enum Reset: string
{
    case HARD = 'HARD';
    case SOFT = 'SOFT';
}
