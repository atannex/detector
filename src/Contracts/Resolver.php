<?php

declare(strict_types=1);

namespace Atannex\Detector\Contracts;

/**
 * DNS lookups for bot verification; swap it to test or to use your own resolver.
 */
interface Resolver
{
    /** The PTR host for an IP, or null. */
    public function reverse(string $ip): ?string;

    /**
     * The A and AAAA addresses of a host.
     *
     * @return list<string>
     */
    public function forward(string $host): array;
}
