<?php

namespace ProxyNova\RangeOptimizer\Tests;

use PHPUnit\Framework\TestCase;
use ProxyNova\RangeOptimizer\CIDRList;
use ProxyNova\RangeOptimizer\RangeOptimizer;

class AmazonTest extends TestCase
{
    public static function getAmazonRangesCached(): array
    {
        if (!isset($GLOBALS["__AMAZON_RANGES_CACHED"])) {
            // $GLOBALS["__AMAZON_RANGES_CACHED"] = false;
        }

        $contents = file_get_contents("https://ip-ranges.amazonaws.com/ip-ranges.json");
        $ranges = json_decode($contents, true);

        return array_map(function ($prefix) {
            return $prefix['ip_prefix'];
        }, $ranges['prefixes']);
    }

    public function test_ruby()
    {
        $input = CIDRList::fromArray([
            '192.168.1.0/26',
            '192.168.1.64/27',
            '192.168.1.96/27',
            '10.1.0.0/26',
            '10.1.0.64/26'
        ]);

        $expected = CIDRList::fromArray([
            '10.1.0.0/25',
            '192.168.1.0/25'
        ]);

        $temp = new RangeOptimizer();

        $result = $temp->optimize($input);

        $this->assertEquals($expected, $result);
    }

    public function test_amazon()
    {
        $prefixes = $this->getAmazonRangesCached();
        $prefixCount = count($prefixes);

        $this->assertGreaterThan(100, $prefixCount);

        // how many IP ranges to test at once?
        $sliceLen = 300;

        $temp = new RangeOptimizer();

        // how many tests to perform?
        for ($i = 0; $i < 10; $i++) {
            $randomSlice = array_slice($prefixes, mt_rand(0, $prefixCount - $sliceLen), $sliceLen);

            $listBefore = CIDRList::fromArray($randomSlice);

            $optimized = $temp->optimize($listBefore);

            // check if subnets contained in list1 are also contained in list2
            $this->assertTrue($optimized->equalSubnets($listBefore));
        }
    }
}