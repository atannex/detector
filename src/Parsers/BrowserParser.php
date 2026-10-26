<?php

declare(strict_types=1);

namespace Atannex\Detector\Parsers;

use Atannex\Detector\ClientHints;
use Atannex\Detector\Data\Browser;
use Atannex\Detector\Data\Engine;
use Atannex\Detector\Data\Os;

/** @internal */
final class BrowserParser
{
    /** Sec-CH-UA brand → browser name, family. */
    private const BRANDS = [
        'google chrome' => ['Chrome', 'Chrome'],
        'chromium' => ['Chromium', 'Chrome'],
        'microsoft edge' => ['Microsoft Edge', 'Chrome'],
        'microsoft edge webview2' => ['Edge WebView2', 'Chrome'],
        'opera' => ['Opera', 'Chrome'],
        'opera gx' => ['Opera GX', 'Chrome'],
        'brave' => ['Brave', 'Chrome'],
        'yandex' => ['Yandex Browser', 'Chrome'],
        'yabrowser' => ['Yandex Browser', 'Chrome'],
        'samsung internet' => ['Samsung Internet', 'Chrome'],
        'duckduckgo' => ['DuckDuckGo', 'Chrome'],
        'vivaldi' => ['Vivaldi', 'Chrome'],
        'whale' => ['Naver Whale', 'Chrome'],
        'avast secure browser' => ['Avast Secure Browser', 'Chrome'],
        'avg secure browser' => ['AVG Secure Browser', 'Chrome'],
        'ccleaner browser' => ['CCleaner Browser', 'Chrome'],
        'android webview' => ['Chrome WebView', 'Chrome'],
        'headlesschrome' => ['Headless Chrome', 'Chrome'],
        'huawei browser' => ['Huawei Browser', 'Chrome'],
        'miui browser' => ['MIUI Browser', 'Chrome'],
        'coc coc' => ['Cốc Cốc', 'Chrome'],
        'arc' => ['Arc', 'Chrome'],
    ];

    /** Names the User-Agent gives every Chromium browser alike: hints may say more. */
    private const GENERIC = ['Chrome', 'Chromium', null];

    public function parse(string $userAgent): Browser
    {
        foreach (Rules::get('browsers') as [$pattern, $name, $family]) {
            if (preg_match($pattern, $userAgent, $m)) {
                return new Browser($name, Rules::version($m), $family);
            }
        }

        return Browser::unknown();
    }

    /**
     * @return array{0: Browser, 1: bool} the browser, and whether the hints say it is a WebView
     */
    public function fromHints(ClientHints $hints, Browser $browser): array
    {
        $chosen = $hints->brand();

        if ($chosen === null) {
            return [$browser, false];
        }

        [$brand, $version] = $chosen;
        [$name, $family] = self::BRANDS[strtolower($brand)] ?? [$brand, 'Chrome'];
        $webview = $name === 'Chrome WebView';

        if (in_array($browser->name, self::GENERIC, true) || $browser->name === $name) {
            return [new Browser($name, self::fuller($version, $browser->name === $name ? (string) $browser->version : null), $family), $webview];
        }

        return [$browser, $webview];
    }

    public function engine(string $userAgent, Browser $browser, Os $os, ClientHints $hints): Engine
    {
        $capture = fn (string $pattern) => preg_match($pattern, $userAgent, $m) ? $m[1] : null;

        // Every browser on iOS and iPadOS uses WebKit.
        if ($os->is('iOS', 'iPadOS', 'watchOS', 'tvOS', 'visionOS')) {
            return new Engine('WebKit', $capture('~AppleWebKit/([\d.]+)~i'));
        }

        return match (true) {
            $browser->is('Pale Moon', 'Basilisk', 'Mypal') => new Engine('Goanna', $capture('~Goanna/([\d.]+)~i')),
            $browser->family === 'Edge' => new Engine('EdgeHTML', $capture('~Edge/([\d.]+)~i')),
            str_contains($userAgent, 'Trident/') => new Engine('Trident', $capture('~Trident/([\d.]+)~i')),
            (bool) preg_match('~MSIE \d~', $userAgent) => new Engine('Trident', null),
            str_contains($userAgent, 'Presto/') => new Engine('Presto', $capture('~Presto/([\d.]+)~i')),
            str_contains($userAgent, 'Opera Mini/') => new Engine('Presto', null),
            (bool) preg_match('~Chrome/(\d+)~', $userAgent, $c) && (int) $c[1] >= 28 => new Engine('Blink', $hints->fullVersions['Chromium'] ?? $capture('~Chrome/([\d.]+)~')),
            isset($hints->brands['Chromium']) => new Engine('Blink', $hints->fullVersions['Chromium'] ?? $hints->brands['Chromium']),
            str_contains($userAgent, 'AppleWebKit/') => new Engine('WebKit', $capture('~AppleWebKit/([\d.]+)~i')),
            (bool) preg_match('~\bGecko/~', $userAgent) || $browser->family === 'Firefox' => new Engine('Gecko', $capture('~rv:([\d.]+)~i')),
            str_contains($userAgent, 'KHTML') => new Engine('KHTML', $capture('~KHTML/([\d.]+)~i')),
            $browser->is('NetSurf') => new Engine('NetSurf', $browser->version),
            $browser->is('Ladybird') => new Engine('LibWeb', null),
            $browser->is('Lynx', 'Links', 'ELinks', 'w3m') => new Engine('Text', null),
            default => Engine::unknown(),
        };
    }

    /** The hint's version, or the User-Agent's when it is the more precise one. */
    private static function fuller(string $hint, ?string $agent): string
    {
        return $agent !== null && $agent !== '' && str_starts_with($agent, $hint) ? $agent : $hint;
    }
}
