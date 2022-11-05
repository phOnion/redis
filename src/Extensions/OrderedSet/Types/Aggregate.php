<?php

declare(strict_types=1);

namespace Onion\Framework\Redis\Extensions\OrderedSet\Types;

enum Aggregate: string
{
    case SUM = 'SUM';
    case MAX = 'MAX';
    case MIN = 'MIN';
}
