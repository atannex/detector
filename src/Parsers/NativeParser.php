<?php

declare(strict_types=1);

namespace Atannex\Detector\Parsers;

use Atannex\Detector\ClientHints;
use Atannex\Detector\Contracts\Parser;
use Atannex\Detector\Data\Bot;
use Atannex\Detector\Data\Browser;
use Atannex\Detector\Data\Os;
use Atannex\Detector\Data\Probe;
use Atannex\Detector\Device;
use Atannex\Detector\Enums\BotCategory;
use Atannex\Detector\Enums\DeviceType;

/**
 * The built-in parser: the User-Agent first, then Client Hints over it
 * (they are what Chromium no longer puts in the User-Agent), then the
 * probe over both (what no header carries).
 */
final class NativeParser implements Parser
{
    /** Fire OS releases by the Android version they are built on. */
    private const FIRE_OS = ['11' => '8', '9' => '7', '7' => '6', '5' => '5', '4' => '4'];

    private readonly BotParser $bots;

    private readonly OsParser $systems;

    private readonly BrowserParser $browsers;

    private readonly DeviceParser $devices;

    /**
     * @param  bool  $emptyIsBot  an empty or missing User-Agent counts as a bot
     */
    public function __construct(private readonly bool $emptyIsBot = true)
    {
        $this->bots = new BotParser;
        $this->systems = new OsParser;
        $this->browsers = new BrowserParser;
        $this->devices = new DeviceParser;
    }

    public function parse(string $userAgent, ClientHints $hints = new ClientHints, ?Probe $probe = null): Device
    {
        $userAgent = trim(mb_substr($userAgent, 0, 2048));
        $sources = $userAgent === '' ? [] : ['user-agent'];

        $bot = $this->bots->parse($userAgent);
        $app = $this->app($userAgent);
        $os = $this->systems->parse($userAgent);
        $browser = $this->browsers->parse($userAgent);
        [$type, $brand, $model] = $this->devices->parse($userAgent, $os);
        $webview = (bool) preg_match('~; ?wv\)|\bVersion/[\d.]+ Chrome/~', $userAgent) && $os->family === 'android';
        $architecture = null;
        $bitness = null;

        // An iOS app's own view: WebKit without the "Safari/" token.
        if ($os->name === 'iOS' && ! $browser->isKnown() && str_contains($userAgent, 'AppleWebKit/')) {
            $browser = new Browser('iOS WebView', $os->version, 'Safari');
            $webview = true;
        }

        if ($browser->is('Safari') && $os->family === 'android') {
            $browser = new Browser('Android Browser', $browser->version, 'Safari');
        }

        if ($webview && $browser->is('Chrome')) {
            $browser = new Browser('Chrome WebView', $browser->version, 'Chrome');
        }

        if (preg_match('~\b(?:WOW64|Win64|x64|x86_64|amd64)\b~i', $userAgent)) {
            [$architecture, $bitness] = ['x86', 64];
        } elseif (preg_match('~\b(?:aarch64|arm64)\b~i', $userAgent)) {
            [$architecture, $bitness] = ['arm', 64];
        } elseif (preg_match('~\b(?:i[3-6]86|x86)\b~i', $userAgent)) {
            [$architecture, $bitness] = ['x86', 32];
        }

        if (! $hints->isEmpty()) {
            $sources[] = 'client-hints';
            $os = $this->systems->fromHints($hints, $os);
            [$browser, $hintedWebview] = $this->browsers->fromHints($hints, $browser);
            $webview = $webview || $hintedWebview;
            [$type, $brand, $model] = $this->devices->fromHints($hints, $type, $brand, $model);
            $architecture = $hints->architecture !== null ? strtolower($hints->architecture) : $architecture;
            $bitness = $hints->bitness !== null && ctype_digit($hints->bitness) ? (int) $hints->bitness : $bitness;
        }

        if ($probe !== null) {
            $sources[] = 'probe';

            // iPadOS asks for desktop sites and says "Macintosh"; a Mac has no touch screen.
            if ($os->name === 'macOS' && ($probe->touchPoints ?? 0) > 1) {
                $type = DeviceType::Tablet;
                [$brand, $model] = ['Apple', 'iPad'];
                $os = new Os('iPadOS', $browser->is('Safari') ? $browser->version : null, 'apple');
            }
        }

        if ($os->name === 'iOS' && $model === 'iPad' && ($os->version->major() ?? 13) >= 13) {
            $os = $os->with('iPadOS');
        }

        if ($os->name === 'Android' && $brand === 'Amazon') {
            $os = new Os('Fire OS', self::FIRE_OS[(string) $os->version->major()] ?? null, 'android');
        }

        if ($os->name === 'Android' && $type === DeviceType::Tv) {
            $os = $os->with('Android TV');
        }

        $engine = $this->browsers->engine($userAgent, $browser, $os, $hints);

        if ($bot === null && $browser->is('Headless Chrome')) {
            $bot = new Bot('Headless Chrome', BotCategory::Automation, 'Google');
        }

        if ($bot === null && $userAgent === '' && $this->emptyIsBot) {
            $bot = new Bot('Empty user agent', BotCategory::Other);
        }

        return new Device(
            userAgent: $userAgent,
            type: $type,
            brand: $brand,
            model: $model,
            os: $os,
            browser: $browser,
            engine: $engine,
            bot: $bot,
            app: $app,
            webview: $webview,
            architecture: $architecture,
            bitness: $bitness,
            probe: $probe,
            sources: $sources,
        );
    }

    private function app(string $userAgent): ?string
    {
        if ($userAgent === '' || ! preg_match(Rules::any('apps'), $userAgent)) {
            return null;
        }

        foreach (Rules::get('apps') as [$pattern, $name]) {
            if (preg_match($pattern, $userAgent)) {
                return $name;
            }
        }

        return null;
    }
}
