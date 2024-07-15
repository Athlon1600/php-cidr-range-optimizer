<?php

use ProxyNova\RangeOptimizer\CIDR;

/**
 * can return multiple ranges
 *
 * @param int $start
 * @param int $end
 * @return CIDR[]
 */
function range_to_cidr(int $start, int $end): array
{
    $ranges = [];
    $maxSize = 32;

    // until no ranges left
    while ($start <= $end) {

        $prefix = long2ip($start);
        $diff = $end - $start;

        $bits = 32 - log($diff + 1, 2);

        // larger length => smaller range
        $bits = min($maxSize, ceil($bits));
        $ranges[] = new CIDR($prefix . '/' . $bits);

        // how many IPs was that?
        $ipCount = pow(2, 32 - $bits);

        $start += $ipCount;
    }

    return $ranges;
}