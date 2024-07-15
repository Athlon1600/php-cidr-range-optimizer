<?php

namespace ProxyNova\RangeOptimizer;

use ArrayIterator;

/**
 * @implements \IteratorAggregate<int, CIDR>
 */
class CIDRList implements \IteratorAggregate, \Countable
{
    /** @var CIDR[] */
    protected array $sortedRanges = [];

    /**
     * @param CIDR[] $ranges
     */
    protected function __construct(array $ranges)
    {
        $this->sort($ranges);

        // remove duplicates is very easy when sorted - O(n) time
        $unique = $this->uniqueFromSorted($ranges);

        $this->sortedRanges = $unique;
    }

    /**
     * does validation too
     *
     * @param string[]|CIDR[] $ranges
     * @return self
     */
    public static function fromArray(array $ranges): self
    {
        $mapped = array_map(function ($cidr) {

            if ($cidr instanceof CIDR) {
                return $cidr;
            }

            try {
                return new CIDR($cidr);
            } catch (\InvalidArgumentException $e) {
                return null;
            }
        }, $ranges);

        $mapped = array_filter($mapped);

        return new self($mapped);
    }

    /**
     * @param CIDR[] $array
     * @return CIDR[]
     */
    protected function uniqueFromSorted(array $array): array
    {
        $unique = [];
        /** @var CIDR|null $previous */
        $previous = null;

        for ($i = 0; $i < count($array); $i++) {

            // first element OR current element different from previous
            if ($previous === null || !$array[$i]->equals($previous)) {
                $unique[] = $array[$i];
                $previous = $array[$i];
            }
        }

        return $unique;
    }

    /**
     *
     * uses quicksort under hood = O(n log n)
     * @param CIDR[] $cidr_list
     * @return void
     */
    protected function sort(array &$cidr_list): void
    {
        // Sort the ranges by their start point
        usort($cidr_list, function (CIDR $a, CIDR $b) {

            // if they have the same start, sort by their end point in descending order
            if ($a->getFirstAddressLong() == $b->getFirstAddressLong()) {
                return $b->getLastAddressLong() - $a->getLastAddressLong();
            }

            return $a->getFirstAddressLong() - $b->getFirstAddressLong();
        });
    }

    /**
     * WARNING: not necessarily unique hosts as it may contain overlapping address ranges
     * @return int
     */
    public function getHostCount(): int
    {
        return array_sum(array_map(function (CIDR $cidr) {
            return $cidr->getHostCount();
        }, $this->sortedRanges));
    }

    /**
     * Iterate sequentially over all CIDRs ignoring overlapping ranges
     * @return \Generator
     */
    protected function iteratorWithoutOverlaps(): \Generator
    {
        $previous = null;

        foreach ($this->sortedRanges as $range) {
            $start = $range->getFirstAddressLong();

            while ($start <= $range->getLastAddressLong()) {

                // ranges sometimes overlap - we want to ignore those
                if ($previous === null || $start > $previous) {
                    yield $start;
                    $previous = $start;
                }

                $start++;
            }
        }
    }

    // same list of subnets can be represented differently using CIDR notation
    // we only care if the subnets are the same in each list
    public function equalSubnets(CIDRList $other): bool
    {
        if ($this->getHostCount() != $other->getHostCount()) {
            // TODO: cannot possibly be equal if one subnet is larger than the other
        }

        $first = $this->iteratorWithoutOverlaps();
        $second = $other->iteratorWithoutOverlaps();

        $same = true;

        // loop through both list simultaneously and return false if any mismatch found
        while ($first->valid() && $second->valid()) {
            $ip1 = $first->current();
            $ip2 = $second->current();

            if ($ip1 != $ip2) {
                $same = false;
                break;
            }

            $first->next();
            $second->next();
        }

        return $same;
    }

    /**
     * @return CIDR[]
     */
    public function toArray(): array
    {
        return $this->sortedRanges;
    }

    public function __toString(): string
    {
        $temp = array_map(function (CIDR $range) {
            return strval($range);
        }, $this->toArray());

        return implode(PHP_EOL, $temp);
    }

    /**
     * @return ArrayIterator<int, CIDR>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->sortedRanges);
    }

    public function count(): int
    {
        return count($this->sortedRanges);
    }
}