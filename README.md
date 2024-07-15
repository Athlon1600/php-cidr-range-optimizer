## Optimize CIDR Ranges

[![Minimum PHP Version](https://img.shields.io/badge/php-%20%3E%3D7.4-blue.svg)](https://php.net/)
![GitHub Workflow Status (with event)](https://img.shields.io/github/actions/workflow/status/Athlon1600/php-cidr-range-optimizer/ci.yml)

Given a list of IP address ranges, minify that list to the smallest possible size by performing the following
optimizations:

- removing duplicate IP ranges from the list
- removing IP ranges already covered by larger ranges in the list
- merging adjacent IP ranges into larger, contiguous blocks

## Installation

```shell
composer require athlon1600/php-cidr-range-optimizer
```

## Usage

Build your list of IP ranges into `CIDRList` object, then use `RangeOptimizer` class to optimize it. Example:

```php
use ProxyNova\RangeOptimizer\CIDR;
use ProxyNova\RangeOptimizer\RangeOptimizer;

$ranges = CIDRList::fromArray([
    "192.168.1.0/26",
    "192.168.1.64/27",
    "192.168.1.96/27",
    "10.1.0.0/26",
    "10.1.0.64/26"
]);

// returns new optimized CIDRList object
$optimized = RangeOptimizer::optimize($ranges);

echo $optimized;
```

Output:

```text
10.1.0.0/25
192.168.1.0/25
```
