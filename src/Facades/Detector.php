<?php

declare(strict_types=1);

namespace Atannex\Detector\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Atannex\Detector\Device detect(?\Illuminate\Http\Request $request = null)
 * @method static \Atannex\Detector\Device parse(string $userAgent, \Atannex\Detector\ClientHints|array $hints = [], \Atannex\Detector\Data\Probe|string|null $probe = null)
 * @method static bool|null verifyBot(?\Illuminate\Http\Request $request = null)
 * @method static list<string> clientHints()
 * @method static \Atannex\Detector\Contracts\Parser parser()
 * @method static void extend(string $name, \Closure $factory)
 * @method static void usingParser(\Atannex\Detector\Contracts\Parser $parser)
 * @method static \Atannex\Detector\Device fake(\Atannex\Detector\Device|array|string $device = [])
 * @method static void stopFaking()
 * @method static bool isFaking()
 * @method static void flush()
 * @method static mixed config(?string $key = null, mixed $default = null)
 * @method static \Atannex\Detector\Data\Probe|null probeFrom(\Illuminate\Http\Request $request)
 * @method static bool isMobile()
 * @method static bool isSmartphone()
 * @method static bool isFeaturePhone()
 * @method static bool isTablet()
 * @method static bool isHandheld()
 * @method static bool isDesktop()
 * @method static bool isTv()
 * @method static bool isConsole()
 * @method static bool isWearable()
 * @method static bool isXr()
 * @method static bool isCar()
 * @method static bool isEReader()
 * @method static bool isTouch()
 * @method static bool isBot()
 * @method static bool isHuman()
 * @method static bool isAiBot()
 * @method static bool isAiCrawler()
 * @method static bool isSearchEngine()
 * @method static bool isLinkPreview()
 * @method static bool isIos()
 * @method static bool isAndroid()
 * @method static bool isWindows()
 * @method static bool isMac()
 * @method static bool isApple()
 * @method static bool isLinux()
 * @method static bool isChromeOs()
 * @method static bool isInApp()
 * @method static bool isWebView()
 * @method static bool isStandalone()
 * @method static bool is(string ...$names)
 * @method static string name()
 * @method static string label()
 * @method static string classes(string $prefix = '')
 * @method static array<string, mixed> toArray()
 *
 * @see \Atannex\Detector\Detector
 * @see \Atannex\Detector\Device
 */
final class Detector extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Atannex\Detector\Detector::class;
    }
}
