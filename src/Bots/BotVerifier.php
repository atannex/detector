<?php

declare(strict_types=1);

namespace Atannex\Detector\Bots;

use Atannex\Detector\Contracts\Resolver;
use Atannex\Detector\Data\Bot;
use Illuminate\Contracts\Cache\Repository;

/**
 * Anyone can send "Googlebot". The real one comes from an IP whose PTR
 * record is under googlebot.com or google.com and that host resolves back
 * to the same IP: the check Google, Bing, Apple and Yandex document.
 */
final class BotVerifier
{
    /**
     * @param  array<string, list<string>>  $domains  bot name => allowed host suffixes
     */
    public function __construct(
        private readonly Resolver $resolver,
        private readonly array $domains,
        private readonly ?Repository $cache = null,
        private readonly int $ttl = 86400,
    ) {}

    /**
     * True when verified, false when the claim is fake, null when there is
     * nothing to verify against (the bot has no configured domains).
     */
    public function verify(Bot|string $bot, ?string $ip): ?bool
    {
        $name = $bot instanceof Bot ? $bot->name : $bot;
        $domains = $this->domainsFor($name);

        if ($domains === null) {
            return null;
        }

        if ($ip === null || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $check = fn () => $this->check($ip, $domains);

        return $this->cache === null
            ? $check()
            : (bool) $this->cache->remember('detector:bot:'.md5($name.'|'.$ip), $this->ttl, $check);
    }

    /**
     * @return list<string>|null
     */
    public function domainsFor(string $name): ?array
    {
        foreach ($this->domains as $bot => $domains) {
            if (strcasecmp($bot, $name) === 0) {
                return array_values((array) $domains);
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $domains
     */
    private function check(string $ip, array $domains): bool
    {
        $host = $this->resolver->reverse($ip);

        if ($host === null) {
            return false;
        }

        $matches = false;

        foreach ($domains as $domain) {
            $domain = strtolower(ltrim($domain, '.'));

            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                $matches = true;
                break;
            }
        }

        if (! $matches) {
            return false;
        }

        $normalise = fn (string $address) => inet_pton($address) ?: $address;

        return in_array($normalise($ip), array_map($normalise, $this->resolver->forward($host)), true);
    }
}
