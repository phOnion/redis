<?php

namespace Onion\Framework\Redis\Extensions\Stream\Types;

enum Trim: string
{
    case LENGTH = 'MAXLEN';
    case LENGTH_APPROX = 'MAXLEN ~';
    case MINID = 'MINID';
    case MINID_APPROX = 'MINID ~';
}
