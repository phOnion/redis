<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\OrderedSet\Types;

enum Range: string
{
    case RANGE = 'BYRANGE';
    case SCORE = 'BYSCORE';
}
