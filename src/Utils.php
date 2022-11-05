<?php

namespace Onion\Framework\Redis;

use function Onion\Framework\merge;

class Utils
{
    public static function normalizeArray(array $values)
    {
        $results = [];
        for ($i = 0; $i < count($values); $i++) {
            if (isset($values[$i + 1])) {
                $results[$values[$i]] = is_array($values[$i + 1]) ? static::normalizeArray($values[$i + 1]) : $values[$i + 1];
            } else {
                $results[] = $values[$i];
            }
            $i++;
        }

        return $results;
    }
}
