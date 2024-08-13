<?php

namespace ProxyNova\RangeOptimizer;

class CIDR
{
    protected string $prefix;
    protected int $length;

    // network address
    protected int $start;
    // broadcast address
    protected int $end;

    /**
     * @param string $cidrNotation
     * @throws \InvalidArgumentException
     */
    public final function __construct(string $cidrNotation)
    {
        $this->parseOrFail($cidrNotation);
    }

    public static function fromString(string $cidrNotation): CIDR
    {
        return new static($cidrNotation);
    }

    public static function create(string $prefix, int $bits): CIDR
    {
        return new static($prefix . '/' . $bits);
    }

    protected function parseOrFail(string $cidrNotation): void
    {
        $onlyIp = filter_var(trim($cidrNotation), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);

        if ($onlyIp) {
            $this->parseOrFail($onlyIp . '/32');
            return;
        }

        $parts = explode('/', $cidrNotation);

        if (count($parts) !== 2) {
            throw new \InvalidArgumentException('Invalid CIDR: ' . $cidrNotation);
        }

        // The starting IP address is given before the slash (/)
        $address = $parts[0];
        $prefix = intval($parts[1]);

        if ($prefix < 0 || $prefix > 32) {
            throw new \InvalidArgumentException('Invalid CIDR prefix: ' . $cidrNotation);
        }

        $start = ip2long($address);

        if ($start === false) {
            throw new \InvalidArgumentException('Invalid IP address: ' . $address);
        }

        $mask = -1 << (32 - $prefix);
        $networkAddress = ip2long($address) & $mask;
        $broadcastAddress = $networkAddress | ~$mask;

        $this->prefix = $address;
        $this->length = $prefix;

        $this->start = $networkAddress;
        $this->end = $broadcastAddress;
    }

    public function getFirstAddress(): string
    {
        return long2ip($this->start) ?: "";
    }

    public function getFirstAddressLong(): int
    {
        return $this->start;
    }

    public function getLastAddress(): string
    {
        return long2ip($this->end) ?: "";
    }

    public function getLastAddressLong(): int
    {
        return $this->end;
    }

    public function getHostCount(): int
    {
        return ($this->end - $this->start) + 1;
    }

    public function expand(): \Generator
    {
        $start = $this->getFirstAddressLong();

        for ($i = 0; $i < $this->getHostCount(); $i++) {
            yield $start + $i;
        }
    }

    // check if $other is a subset of this subnet range?
    // check if start-end ranges of $other fall within this range
    public function includes(CIDR $other): bool
    {
        return ($this->getFirstAddressLong() <= $other->getFirstAddressLong() && $this->getLastAddressLong() >= $other->getLastAddressLong());
    }

    public function equals(CIDR $other): bool
    {
        return $this->getFirstAddressLong() === $other->getFirstAddressLong() && $this->getLastAddressLong() === $other->getLastAddressLong();
    }

    public function __toString(): string
    {
        return $this->prefix . "/" . $this->length;
    }
}