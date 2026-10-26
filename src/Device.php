<?php

declare(strict_types=1);

namespace Atannex\Detector;

use Atannex\Detector\Data\Bot;
use Atannex\Detector\Data\Browser;
use Atannex\Detector\Data\Engine;
use Atannex\Detector\Data\Os;
use Atannex\Detector\Data\Probe;
use Atannex\Detector\Enums\BotCategory;
use Atannex\Detector\Enums\DeviceType;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Stringable;

/**
 * Everything known about the client behind a request.
 *
 *   $device->type            DeviceType::Smartphone
 *   $device->brand, model    "Samsung", "SM-S918B"
 *   $device->os              Os { name: "Android", version: 14, family: "android" }
 *   $device->browser         Browser { name: "Samsung Internet", version: 23.0, family: "Chrome" }
 *   $device->engine          Engine { name: "Blink", version: 115 }
 *   $device->bot             Bot { name: "GPTBot", category: ai-crawler } or null
 *   $device->app             "Instagram" when opened in an app's browser, or null
 *
 * @implements Arrayable<string, mixed>
 */
final class Device implements Arrayable, Jsonable, JsonSerializable, Stringable
{
    /**
     * @param  list<string>  $sources  what the answer was built from: user-agent, client-hints, probe, fake, driver name
     */
    public function __construct(
        public readonly string $userAgent = '',
        public readonly DeviceType $type = DeviceType::Unknown,
        public readonly ?string $brand = null,
        public readonly ?string $model = null,
        public readonly Os $os = new Os,
        public readonly Browser $browser = new Browser,
        public readonly Engine $engine = new Engine,
        public readonly ?Bot $bot = null,
        public readonly ?string $app = null,
        public readonly bool $webview = false,
        public readonly ?string $architecture = null,
        public readonly ?int $bitness = null,
        public readonly ?Probe $probe = null,
        public readonly array $sources = [],
    ) {}

    /**
     * Build one by hand, mostly for Detector::fake():
     * Device::make(['type' => 'tablet', 'os' => 'iPadOS 17', 'browser' => 'Safari 17.4']).
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function make(array $attributes = []): self
    {
        $split = function (mixed $value): array {
            if (is_array($value)) {
                return [$value['name'] ?? $value[0] ?? null, $value['version'] ?? $value[1] ?? null, $value['family'] ?? $value[2] ?? null];
            }

            if (! is_string($value) || $value === '') {
                return [null, null, null];
            }

            return preg_match('/^(.*?)\s+(\d[\d._]*)$/', $value, $m) ? [$m[1], $m[2], null] : [$value, null, null];
        };

        [$os, $osVersion, $osFamily] = $split($attributes['os'] ?? null);
        [$browser, $browserVersion, $browserFamily] = $split($attributes['browser'] ?? null);
        [$engine, $engineVersion] = $split($attributes['engine'] ?? null);

        $type = $attributes['type'] ?? DeviceType::Unknown;
        $type = $type instanceof DeviceType ? $type : (DeviceType::fromName((string) $type) ?? DeviceType::Unknown);

        $bot = $attributes['bot'] ?? null;

        if (is_string($bot)) {
            $bot = new Bot($bot, BotCategory::fromName((string) ($attributes['bot_category'] ?? 'other')) ?? BotCategory::Other);
        } elseif (is_array($bot)) {
            $bot = new Bot((string) ($bot['name'] ?? 'Bot'), BotCategory::fromName((string) ($bot['category'] ?? 'other')) ?? BotCategory::Other, $bot['producer'] ?? null, $bot['url'] ?? null);
        }

        $probe = $attributes['probe'] ?? null;

        return new self(
            userAgent: (string) ($attributes['user_agent'] ?? ''),
            type: $type,
            brand: $attributes['brand'] ?? null,
            model: $attributes['model'] ?? null,
            os: new Os($os, $osVersion, $osFamily ?? ($os === null ? null : self::familyOf($os))),
            browser: new Browser($browser, $browserVersion, $browserFamily ?? $browser),
            engine: new Engine($engine, $engineVersion),
            bot: $bot instanceof Bot ? $bot : null,
            app: $attributes['app'] ?? null,
            webview: (bool) ($attributes['webview'] ?? false),
            architecture: $attributes['architecture'] ?? null,
            bitness: isset($attributes['bitness']) ? (int) $attributes['bitness'] : null,
            probe: $probe instanceof Probe ? $probe : (is_array($probe) ? new Probe(...$probe) : null),
            sources: (array) ($attributes['sources'] ?? ['fake']),
        );
    }

    /**
     * A copy with some values replaced (named like the constructor).
     */
    public function with(mixed ...$changes): self
    {
        $values = get_object_vars($this);

        foreach ($changes as $key => $value) {
            if (! array_key_exists((string) $key, $values)) {
                throw new \InvalidArgumentException("Unknown device property [{$key}].");
            }

            $values[$key] = $value;
        }

        return new self(...$values);
    }

    // Kind of device -------------------------------------------------------

    /** A phone: smartphones and feature phones. Tablets are not mobile; see isHandheld(). */
    public function isMobile(): bool
    {
        return $this->type->isMobile();
    }

    public function isSmartphone(): bool
    {
        return $this->type === DeviceType::Smartphone;
    }

    public function isFeaturePhone(): bool
    {
        return $this->type === DeviceType::FeaturePhone;
    }

    public function isTablet(): bool
    {
        return $this->type === DeviceType::Tablet;
    }

    /** Phones, tablets, watches, e-readers, media players. */
    public function isHandheld(): bool
    {
        return $this->type->isHandheld();
    }

    public function isDesktop(): bool
    {
        return $this->type === DeviceType::Desktop;
    }

    public function isTv(): bool
    {
        return $this->type === DeviceType::Tv;
    }

    public function isConsole(): bool
    {
        return $this->type === DeviceType::Console;
    }

    public function isWearable(): bool
    {
        return $this->type === DeviceType::Wearable;
    }

    public function isXr(): bool
    {
        return $this->type === DeviceType::Xr;
    }

    public function isCar(): bool
    {
        return $this->type === DeviceType::Car;
    }

    public function isEReader(): bool
    {
        return $this->type === DeviceType::EReader;
    }

    /** Touch screen: from the probe when it ran, otherwise inferred from the type. */
    public function isTouch(): bool
    {
        return $this->probe?->isTouch() ?? in_array($this->type, [DeviceType::Smartphone, DeviceType::Tablet, DeviceType::Wearable, DeviceType::SmartDisplay, DeviceType::Car], true);
    }

    // Bots -----------------------------------------------------------------

    public function isBot(): bool
    {
        return $this->bot !== null;
    }

    public function isHuman(): bool
    {
        return $this->bot === null;
    }

    /** Any AI fetcher: training crawlers, AI search and assistant fetches. */
    public function isAiBot(): bool
    {
        return $this->bot?->category->isAi() ?? false;
    }

    public function isAiCrawler(): bool
    {
        return $this->bot?->category === BotCategory::AiCrawler;
    }

    public function isSearchEngine(): bool
    {
        return $this->bot?->category === BotCategory::Search;
    }

    /** Link-preview fetchers: Facebook, X, LinkedIn, Slack, WhatsApp, Discord… */
    public function isLinkPreview(): bool
    {
        return $this->bot?->category === BotCategory::Social;
    }

    // Software -------------------------------------------------------------

    public function isIos(): bool
    {
        return $this->os->is('iOS', 'iPadOS');
    }

    public function isAndroid(): bool
    {
        return $this->os->family === 'android';
    }

    public function isWindows(): bool
    {
        return $this->os->family === 'windows';
    }

    public function isMac(): bool
    {
        return $this->os->is('macOS');
    }

    public function isApple(): bool
    {
        return $this->os->family === 'apple';
    }

    public function isLinux(): bool
    {
        return $this->os->family === 'linux';
    }

    public function isChromeOs(): bool
    {
        return $this->os->family === 'chromeos';
    }

    /** An app's embedded browser (Instagram, TikTok, Facebook…) or a WebView. */
    public function isInApp(): bool
    {
        return $this->app !== null || $this->webview;
    }

    public function isWebView(): bool
    {
        return $this->webview;
    }

    /** Running as an installed web app (needs the probe). */
    public function isStandalone(): bool
    {
        return $this->probe?->standalone === true;
    }

    /**
     * Any of the names, compared without case against the type, OS (and
     * family), browser, engine, brand, app and bot: is('ios', 'android'),
     * is('safari'), is('tablet'), is('samsung'), is('instagram').
     */
    public function is(string ...$names): bool
    {
        foreach ($names as $name) {
            $type = DeviceType::fromName($name);

            if (($type !== null && $type === $this->type)
                || $this->os->is($name) || $this->browser->is($name) || $this->engine->is($name)
                || ($this->brand !== null && strcasecmp($this->brand, $name) === 0)
                || ($this->app !== null && strcasecmp($this->app, $name) === 0)
                || ($this->bot !== null && (strcasecmp($name, 'bot') === 0 || $this->bot->is($name)))) {
                return true;
            }
        }

        return false;
    }

    /** "iPhone", "Samsung SM-S918B", "Desktop". */
    public function name(): string
    {
        if ($this->model !== null && $this->brand !== null) {
            return str_starts_with(strtolower($this->model), strtolower($this->brand)) ? $this->model : $this->brand.' '.$this->model;
        }

        return $this->model ?? $this->brand ?? $this->type->label();
    }

    /** "Chrome 120 on Windows 11", "Instagram on iOS 17.4", "Googlebot". */
    public function label(): string
    {
        if ($this->bot !== null) {
            return $this->bot->name;
        }

        $client = $this->app ?? $this->browser->label();
        $os = $this->os->label();

        return match (true) {
            $client !== null && $os !== null => "{$client} on {$os}",
            default => $client ?? $os ?? 'Unknown device',
        };
    }

    /**
     * CSS hooks for <body>: "device-smartphone os-ios browser-safari touch".
     */
    public function classes(string $prefix = ''): string
    {
        $slug = fn (?string $value) => $value === null ? null : trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-');

        return implode(' ', array_filter([
            $prefix.'device-'.$this->type->value,
            $this->os->name !== null ? $prefix.'os-'.$slug($this->os->name) : null,
            $this->browser->name !== null ? $prefix.'browser-'.$slug($this->browser->name) : null,
            $this->app !== null ? $prefix.'app-'.$slug($this->app) : null,
            $this->isInApp() ? $prefix.'in-app' : null,
            $this->isTouch() ? $prefix.'touch' : $prefix.'no-touch',
            $this->isStandalone() ? $prefix.'standalone' : null,
            $this->isBot() ? $prefix.'bot' : null,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'brand' => $this->brand,
            'model' => $this->model,
            'os' => $this->os->jsonSerialize(),
            'browser' => $this->browser->jsonSerialize(),
            'engine' => $this->engine->jsonSerialize(),
            'bot' => $this->bot?->jsonSerialize(),
            'app' => $this->app,
            'webview' => $this->webview,
            'architecture' => $this->architecture,
            'bitness' => $this->bitness,
            'touch' => $this->isTouch(),
            'probe' => $this->probe?->jsonSerialize(),
            'label' => $this->label(),
            'user_agent' => $this->userAgent,
            'sources' => $this->sources,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson($options = 0): string
    {
        return (string) json_encode($this->toArray(), $options);
    }

    public function __toString(): string
    {
        return $this->label();
    }

    /** @internal */
    public static function familyOf(string $os): string
    {
        return match (strtolower($os)) {
            'windows', 'windows phone', 'windows mobile', 'windows rt' => 'windows',
            'ios', 'ipados', 'macos', 'watchos', 'tvos', 'visionos' => 'apple',
            'android', 'fire os', 'wear os', 'android tv', 'harmonyos' => 'android',
            'chromeos', 'chrome os', 'chromium os' => 'chromeos',
            'linux', 'gnu/linux', 'ubuntu', 'debian', 'fedora', 'arch linux', 'linux mint', 'centos', 'red hat', 'opensuse', 'manjaro', 'gentoo', 'elementary os', 'pop!_os' => 'linux',
            'freebsd', 'openbsd', 'netbsd', 'dragonfly bsd' => 'bsd',
            'playstation', 'xbox', 'nintendo' => 'console',
            default => 'other',
        };
    }
}
