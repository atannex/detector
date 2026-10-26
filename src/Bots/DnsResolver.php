<?php

declare(strict_types=1);

namespace Atannex\Detector\Bots;

use Atannex\Detector\Contracts\Resolver;

final class DnsResolver implements Resolver
{
    public function reverse(string $ip): ?string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $host = @gethostbyaddr($ip);

        return is_string($host) && $host !== $ip && $host !== '' ? strtolower(rtrim($host, '.')) : null;
    }

    public function forward(string $host): array
    {
        $addresses = @gethostbynamel($host) ?: [];

        $records = @dns_get_record($host, DNS_AAAA) ?: [];

        foreach ($records as $record) {
            if (isset($record['ipv6'])) {
                $addresses[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($addresses));
    }
}
