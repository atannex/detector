<?php

declare(strict_types=1);

namespace Atannex\Detector\Parsers;

use Atannex\Detector\Data\Bot;
use Atannex\Detector\Enums\BotCategory;

/** @internal */
final class BotParser
{
    public function parse(string $userAgent): ?Bot
    {
        if ($userAgent === '' || ! preg_match(Rules::any('bots'), $userAgent)) {
            return null;
        }

        foreach (Rules::get('bots') as [$pattern, $name, $category, $producer, $url]) {
            if (preg_match($pattern, $userAgent, $m)) {
                return new Bot(
                    $name ?? self::nameFrom($m[0]),
                    BotCategory::from($category),
                    $producer,
                    $url ?? (preg_match('~\+?(https?://[^\s;)]+)~i', $userAgent, $link) ? rtrim($link[1], '.,') : null),
                );
            }
        }

        return null;
    }

    private static function nameFrom(string $token): string
    {
        $token = trim($token, " \t-_.");

        return $token === '' ? 'Bot' : ucfirst($token);
    }
}
