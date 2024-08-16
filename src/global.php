<?php

use ProxyNova\RangeOptimizer\CIDR;

if (!function_exists('cidr_mask_length')) {

    // Formula: # of IP addresses = 2^(32 - CIDR suffix)
    function cidr_mask_length(int $start, int $end): int
    {
        $prefix = 32 - log($end - $start + 1, 2);
        $prefix = (int)ceil($prefix);

        return min(32, $prefix);
    }
}

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

    // until no ranges left
    while ($start <= $end) {

        $address = long2ip($start);

        // from: https://github.com/allaboutjst/airbnb/blob/master/src/main/java/ip_range_to_cidr/IPRangetoCIDR.java#L41
        // Find the location of the first 1 bit
        $locOfFirstOne = $start & (-$start);

        // Calculate the corresponding mask
        $curMask = 32 - (int)(log($locOfFirstOne) / log(2));

        $maxLen = cidr_mask_length($start, $end);
        $prefixLength = max($curMask, $maxLen);

        $ranges[] = new CIDR($address . '/' . $prefixLength);

        // how many IPs was that?
        $ipCount = pow(2, 32 - $prefixLength);

        $start += $ipCount;
    }

    return $ranges;
}