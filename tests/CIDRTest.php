<?php

namespace ProxyNova\RangeOptimizer\Tests;

use PHPUnit\Framework\TestCase;
use ProxyNova\RangeOptimizer\CIDR;

class CIDRTest extends TestCase
{
    public function test_parse()
    {
        // CIDR => [expected network, expected broadcast]
        $data = [
            "172.233.173.111/8" => ["172.0.0.0", "172.255.255.255"],
            "74.207.237.201/14" => ["74.204.0.0", "74.207.255.255"],
            "45.33.60.45/7" => ["44.0.0.0", "45.255.255.255"]
        ];

        foreach ($data as $input => $outputs) {
            $temp = new CIDR($input);

            $this->assertEquals($outputs[0], $temp->getFirstAddress());
            $this->assertEquals($outputs[1], $temp->getLastAddress());
        }
    }

    public function test_range_to_cidr()
    {
        // start IP, end IP, CIDRs
        $ranges = [
            ["13.49.126.128", "13.49.126.191", ["13.49.126.128/26"]],
            ["18.139.204.176", "18.139.204.223", ["18.139.204.176/28", "18.139.204.192/27"]],
            ["3.25.37.128", "3.25.40.255", ["3.25.37.128/25", "3.25.38.0/23", "3.25.40.0/24"]],
            ["35.80.36.208", "35.80.36.239", ["35.80.36.208/28", "35.80.36.224/28"]]
        ];

        foreach ($ranges as $range) {

            $cidrArray = range_to_cidr(ip2long($range[0]), ip2long($range[1]));
            $expected = $range[2];

            $this->assertEquals(count($expected), count($cidrArray));

            foreach ($expected as $index => $value) {
                $str = strval($cidrArray[$index]);
                $this->assertEquals($value, $str);
            }
        }
    }
}
