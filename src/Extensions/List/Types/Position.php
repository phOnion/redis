<?php

namespace Onion\Framework\Redis\Extensions\List\Types;

enum Position: string
{
    case BEFORE = 'BEFORE';
    case AFTER = 'AFTER';
}
