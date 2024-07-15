<?php

namespace ProxyNova\RangeOptimizer;

class RangeOptimizer
{
    /**
     * Remove ranges already covered by larger ranges in the list.
     * Expects sorted array. worst case O(n^2)
     * @param CIDR[] $ranges
     * @return CIDR[]
     */
    protected static function removeOverlaps(array $ranges): array
    {
        $iter = 0;
        $filtered = [];

        for ($i = 0; $i < count($ranges); $i++) {

            $outer = $ranges[$i];
            $isCovered = false;

            // check if the IP range is covered by any other range
            for ($j = 0; $j < count($ranges); $j++) {
                $iter++;

                $inner = $ranges[$j];

                // looking at same element!
                if ($j == $i) continue;

                if ($inner->includes($outer)) {
                    $isCovered = true;
                    break;
                }

                // stop looking after portion of array where cover could not possibly exist
                if ($inner->getFirstAddressLong() > $outer->getFirstAddressLong()) {
                    break;
                }
            }

            if (!$isCovered) {
                $filtered[] = $outer;
            }
        }

        return $filtered;
    }

    /**
     * worst case O(n)
     * @param CIDR[] $ranges
     * @return CIDR[]
     */
    protected static function mergeAdjacent(array $ranges): array
    {
        if (count($ranges) < 2) {
            return $ranges;
        };

        // want list of ranges [start, end]
        $ranges = array_map(function (CIDR $cidr) {
            return [$cidr->getFirstAddressLong(), $cidr->getLastAddressLong()];
        }, $ranges);

        $merged = [];

        $previous = null;

        // we are expected an array of ranges in ascending order
        foreach ($ranges as $range) {

            if ($previous === null) {
                $previous = $range;
                continue;
            }

            if ($range[0] <= ($previous[1] + 1)) {
                $previous[1] = max($previous[1], $range[1]);
            } else {
                $merged[] = $previous;
                $previous = $range;
            }
        }

        // Add the last range
        if ($previous !== null) {
            $merged[] = $previous;
        }

        // translate back to CIDR list
        $cidr = [];

        foreach ($merged as $value) {
            $temp = range_to_cidr($value[0], $value[1]);
            $cidr = array_merge($cidr, $temp);
        }

        return $cidr;
    }

    /**
     * @param CIDRList $cidr_list
     * @return CIDRList
     */
    public static function optimize(CIDRList $cidr_list): CIDRList
    {
        $ranges = $cidr_list->toArray();

        $ranges = self::removeOverlaps($ranges);
        $ranges = self::mergeAdjacent($ranges);

        $ranges = CIDRList::fromArray($ranges);

        return $ranges;
    }
}