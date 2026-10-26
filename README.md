# atannex/detector

[![Tests](https://github.com/atannex/detector/actions/workflows/tests.yml/badge.svg)](https://github.com/atannex/detector/actions/workflows/tests.yml)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](composer.json)
[![Laravel](https://img.shields.io/badge/Laravel-11%20%7C%2012-FF2D20?logo=laravel&logoColor=white)](composer.json)
[![License: MIT](https://img.shields.io/badge/License-MIT-2ea44f.svg)](LICENSE)

Device detection for Laravel that reads everything a client sends, not just the User-Agent: what kind of device it is, who made it, its OS, browser and engine, whether it is a bot (and which kind, AI crawlers included), whether it is an app's built-in browser, and which devices each of your users signs in from.

```php
$request->device();                 // Device { type: smartphone, brand: "Samsung", model: "SM-S928B", … }
$request->device()->label();        // "Samsung Internet 23 on Android 14"

Detector::isMobile();               // phones
Detector::is('ios', 'android');     // type, OS, browser, brand, app or bot names
Detector::isAiCrawler();            // GPTBot, ClaudeBot, CCBot, Bytespider…
Detector::verifyBot();              // is this "Googlebot" really Google?

Route::get('/app', …)->middleware('device:mobile,tablet');
Route::get('/articles/{article}', …)->middleware('block-bots:ai-crawler');
```

```blade
<body class="@deviceClasses">       {{-- device-smartphone os-ios browser-safari touch --}}
@mobile <x-bottom-nav /> @endmobile
@inapp <p>Open in your browser for the full experience.</p> @endinapp
```

Why another one:

- **The User-Agent is frozen.** Chrome, Edge and every other Chromium browser report Windows 11 as "Windows NT 10.0", replace the Android model with "K", and zero out their versions ("124.0.0.0"). The real values are in [User-Agent Client Hints](https://developer.mozilla.org/docs/Web/HTTP/Client_hints#user-agent_client_hints). This package asks for them (`Accept-CH`) and puts them ahead of the User-Agent, so you get `Windows 11`, `SM-S928B`, `124.0.6367.91` and Brave instead of "Chrome".
- **Some things no header carries.** An iPad in its default "desktop website" mode sends a Mac's User-Agent. A tiny optional script (`@deviceProbe`) reports touch points, screen size and whether the site runs as an installed app, and the next request uses it.
- **Bots, by category.** Over 200 named bots in 14 categories: search, AI training crawlers, AI search, AI assistants fetching for a user, link previews, SEO, monitoring, feeds, archivers, advertising, security scanners, headless browsers and HTTP clients. Bots keep the device they present (Googlebot Smartphone is a smartphone), and the big search crawlers can be **verified by reverse and forward DNS**.
- **Apps and WebViews.** Instagram, Facebook, Messenger, TikTok, WeChat, LinkedIn, X, Snapchat, the Google app and others, plus Android WebViews and iOS WKWebViews, so you can tell people when a link opened inside an app.
- **Devices people use.** Desktops, phones, tablets, feature phones (KaiOS), TVs (Tizen, webOS, Android TV, Fire TV, Roku, Chromecast), consoles, XR headsets, cars, e-readers, watches and smart displays. Brand rules cover Samsung, Apple, Google, Xiaomi, Huawei, Honor, OPPO, vivo, realme, OnePlus, Tecno, Infinix, itel, Motorola, Nokia, Sony, LG and about 50 more.
- **Laravel all the way down.** `$request->device()`, a facade, a `device()` helper, Blade conditionals, route middleware, artisan commands, an in-memory and cache-store result cache, a test fake, and per-user **device tracking with a new-device event**.
- **Swappable.** Prefer [matomo/device-detector](https://github.com/matomo-org/device-detector)'s model database ("Galaxy S23 Ultra" instead of "SM-S918B")? Set `'driver' => 'matomo'`. Its answer is laid over the native one, so Client Hints, the probe, bot categories, apps and WebViews still apply.

## Install

```bash
composer require atannex/detector
php artisan vendor:publish --tag=detector-config        # optional
```

The service provider and the `Detector` facade register themselves. The `client-hints` middleware is added to the `web` group by default (`client_hints.middleware_groups`).

## The device

```php
$device = $request->device();       // or device(), Detector::detect(), Detector::detect($otherRequest)

$device->type;                      // DeviceType::Smartphone (enum)
$device->brand;                     // "Samsung"
$device->model;                     // "SM-S928B"
$device->os;                        // Os { name: "Android", version: Version(14.0.0), family: "android" }
$device->browser;                   // Browser { name: "Samsung Internet", version: 23.0, family: "Chrome" }
$device->engine;                    // Engine { name: "Blink", version: 115.0.0.0 }
$device->bot;                       // Bot { name, category, producer, url } or null
$device->app;                       // "Instagram", or null
$device->webview;                   // true inside a WebView
$device->architecture;              // "arm", "x86"
$device->bitness;                   // 64
$device->probe;                     // Probe { touchPoints, width, height, pixelRatio, standalone } or null
$device->sources;                   // ["user-agent", "client-hints", "probe"]
```

Questions:

| Kind | OS | Bots | Other |
|---|---|---|---|
| `isMobile()` (phones) | `isIos()` (iOS, iPadOS) | `isBot()` / `isHuman()` | `isInApp()` |
| `isSmartphone()` | `isAndroid()` (incl. Fire OS, HarmonyOS) | `isAiBot()` (any AI) | `isWebView()` |
| `isFeaturePhone()` | `isWindows()` | `isAiCrawler()` (training) | `isTouch()` |
| `isTablet()` | `isMac()` | `isSearchEngine()` | `isStandalone()` (installed PWA) |
| `isHandheld()` (phones, tablets, watches…) | `isApple()` | `isLinkPreview()` | `is(...$names)` |
| `isDesktop()`, `isTv()`, `isConsole()` | `isLinux()` | `$device->bot->in('ai', 'seo')` | `name()`, `label()`, `classes()` |
| `isWearable()`, `isXr()`, `isCar()`, `isEReader()` | `isChromeOs()` | `$device->bot->is('GPTBot')` | `toArray()`, JSON |

`is()` compares, ignoring case, against the type, OS name and family, browser, engine, brand, app and bot: `is('tablet')`, `is('ios', 'android')`, `is('samsung')`, `is('instagram')`, `is('bot')`.

Versions compare: `$device->os->version->isAtLeast('17.4')`, `->isBelow(15)`, `->is('17')` (17.x), `->major()`, `->short()` ("124.0.0.0" → "124"). `$device->browser->isBasedOn('Chrome')` holds for Edge, Opera, Samsung Internet and every other Chromium browser.

Any User-Agent, with or without hints:

```php
Detector::parse($userAgent);
Detector::parse($userAgent, ['Sec-CH-UA-Platform' => '"Windows"', 'Sec-CH-UA-Platform-Version' => '"15.0.0"']);
```

```bash
php artisan detector:parse "Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) …"
php artisan detector:parse "…" --hint='Sec-CH-UA-Model: "Pixel 8"' --json
```

## Client Hints

Chromium sends `Sec-CH-UA`, `-Mobile` and `-Platform` on every request. The detailed hints (`-Full-Version-List`, `-Platform-Version`, `-Model`, `-Arch`, `-Bitness`, `-Form-Factors`) arrive once the site asks for them, which the `client-hints` middleware does on HTML responses. They come with the visitor's **next** request. If you need one on the very first request, list it under `client_hints.critical`: the browser retries the first request with it (`Critical-CH`), at the cost of a round trip.

Behind a CDN, make sure it forwards `Sec-CH-UA*` headers and includes them in its cache key, or vary the page on them.

Safari and Firefox do not send Client Hints. For them the User-Agent is still the whole story (Safari's macOS version is frozen at 10.15.7).

## The probe

```blade
<head>
    @deviceProbe
</head>
```

This prints a few hundred bytes of inline JavaScript that stores `touch points | screen size | pixel ratio | display mode` in a `device_probe` cookie. It is first-party, not encrypted, contains nothing identifying, and is rewritten only when the values change. It honours Vite's CSP nonce. From the next request on:

- an iPad that sends a Mac User-Agent is detected as an iPad running iPadOS;
- `isTouch()` reflects the actual screen;
- `isStandalone()` tells you when the site runs as an installed PWA;
- `$device->probe->width`, `height` and `pixelRatio` are available.

Turn it off with `probe.enabled`.

## Bots

```php
$device->bot->name;                 // "GPTBot"
$device->bot->category;             // BotCategory::AiCrawler
$device->bot->producer;             // "OpenAI"
$device->bot->in('ai');             // AiCrawler, AiSearch or AiAssistant
```

| Category | Examples |
|---|---|
| `search` | Googlebot, Bingbot, Applebot, YandexBot, Baiduspider, DuckDuckBot, PetalBot |
| `ai-crawler` | GPTBot, ClaudeBot, CCBot, Bytespider, Amazonbot, Meta-ExternalAgent, Google-CloudVertexBot |
| `ai-search` | OAI-SearchBot, Claude-SearchBot, PerplexityBot, YouBot |
| `ai-assistant` | ChatGPT-User, Claude-User, Perplexity-User, MistralAI-User, DuckAssistBot |
| `social` | facebookexternalhit, Twitterbot, LinkedInBot, Slackbot, Discordbot, WhatsApp, TelegramBot |
| `seo` | AhrefsBot, SemrushBot, MJ12bot, DotBot, Screaming Frog |
| `monitoring` | UptimeRobot, Pingdom, Better Stack, Oh Dear, Lighthouse, health checks |
| `feed`, `archiver`, `advertising` | Feedly, Inoreader · Internet Archive · AdsBot-Google |
| `security` | Censys, zgrab, Nuclei, Nmap, sqlmap, WPScan |
| `automation` | Headless Chrome, Puppeteer, Playwright, Selenium, PhantomJS |
| `http-client` | curl, Wget, python-requests, Go-http-client, axios, Guzzle, Postman |

Anything else that calls itself a bot, crawler, spider or fetcher is caught as `other`. Requests with no User-Agent count as bots (`bots.empty_user_agent`). HTTP libraries used by mobile apps (OkHttp, Dart, CFNetwork) are **not** bots. Those requests are people, and they are reported as `app: "Android app"`, `"Flutter app"` or `"iOS app"`.

### Blocking

```php
Route::middleware('block-bots')->group(…);                       // bots.block.categories: AI training crawlers by default
Route::middleware('block-bots:ai')->group(…);                    // every AI category
Route::middleware('block-bots:ai-crawler,seo,Bytespider')->group(…);
Route::middleware('block-bots:*')->group(…);                     // every bot; bots.block.allow still passes
```

Blocked requests get a 403 (`bots.block.status`) and fire `BotBlocked`. robots.txt asks crawlers to stay away; this enforces it for those that identify themselves.

### Verification

Anyone can send "Googlebot". `Detector::verifyBot()` checks the request IP's PTR record against the crawler's documented hosts, then resolves that host back to the same IP. It returns `true` (genuine), `false` (spoofed) or `null` (not a bot, or no hosts configured). Hosts for Google, Bing, Apple, Yandex, Baidu, Petal, Amazon and others are in `bots.verify`. Results are cached for a day. Bind your own `Atannex\Detector\Contracts\Resolver` to change how DNS is resolved.

```php
if ($request->device()->isSearchEngine() && Detector::verifyBot() === false) {
    abort(403);
}
```

## Routes and Blade

```php
->middleware('device:mobile,tablet')   // only these; others get a 404
->middleware('device:ios,android')
->middleware('device:!bot')            // "!" excludes
->middleware('device:desktop,!in-app')
```

Besides everything `is()` accepts, `device:` knows `mobile`, `handheld`, `touch`, `human`, `in-app` and `standalone`.

```blade
@mobile … @endmobile        @tablet … @endtablet       @desktop … @enddesktop
@handheld … @endhandheld    @touch … @endtouch         @inapp … @endinapp
@standalone … @endstandalone
@device('tv', 'console') … @enddevice
@os('ios') … @endos         @browser('safari') … @endbrowser   {{-- @browser('chrome') includes Chromium browsers --}}
@bot … @endbot              @bot('ai') … @endbot

<body class="@deviceClasses">            {{-- @deviceClasses('is-') to prefix --}}
```

## Device tracking

Remember which devices each user signs in from, and react to a new one:

```bash
php artisan vendor:publish --tag=detector-migrations
php artisan migrate
```

```php
use Atannex\Detector\Concerns\HasDevices;

class User extends Authenticatable
{
    use HasDevices;
}
```

```php
Route::middleware(['web', 'auth', 'track-devices'])->group(…);  // or tracking.enabled + tracking.middleware_groups
```

```php
use Atannex\Detector\Events\NewDeviceDetected;

Event::listen(function (NewDeviceDetected $event) {
    $event->user->notify(new NewSignIn($event->device->label(), $event->record->ip_address));
});
```

A device is a browser profile holding a random id in a long-lived, encrypted, HTTP-only cookie (`device_id`). There is no fingerprinting, and clearing cookies makes it a new device. Tracking runs after the response, so the request that signs a user in counts. It skips bots, ignores a user's very first device (`tracking.notify_first`), and writes `last_seen_at` at most every `touch_every` seconds.

```php
$user->devices;                         // newest first
$user->currentDevice();                 // this request's UserDevice
$device->label();                       // "Chrome on Windows 11", or the name the user gave it
$device->isCurrent();
$device->trust(); $device->isTrusted(); // e.g. to skip a 2FA prompt
$user->devices()->trusted()->get();
$user->forgetDevice($device);
$user->forgetOtherDevices();
```

```bash
php artisan detector:prune --days=365   # schedule it: Schedule::command('detector:prune')->daily()
```

## Drivers and caching

`driver` is `native` (default), `matomo` (needs `composer require matomo/device-detector`), or the class name of your own `Atannex\Detector\Contracts\Parser`. You can also register one at runtime with `Detector::extend('name', fn ($app, $config) => new MyParser)`.

Results are cached in memory per process (`cache.memory` entries, which also helps Octane workers). Set `cache.store` to keep them across requests. An uncached native parse takes well under a millisecond.

## Testing

```php
Detector::fake(['type' => 'tablet', 'os' => 'iPadOS 17.4', 'browser' => 'Safari 17.4', 'brand' => 'Apple']);
Detector::fake('Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) …');
Detector::fake(['bot' => ['name' => 'GPTBot', 'category' => 'ai-crawler']]);

Detector::stopFaking();
```

While faking, every `detect()`, `$request->device()`, `device()`, Blade directive and middleware sees the fake. `Detector::parse()` still parses.

```bash
composer test
```

## What it cannot know

Detection only goes as far as the client lets it. A browser can send any User-Agent. Safari and Firefox do not send Client Hints. A first request has no hints beyond the low-entropy three and no probe. Model codes are reported as sent ("SM-S918B"); use the `matomo` driver for marketing names. Treat the result as a strong hint for adapting the experience and spotting bots, never as proof for security decisions. The one exception is `verifyBot()`, which checks DNS rather than trusting headers.

## License

MIT. See [LICENSE](LICENSE).
