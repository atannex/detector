<?php

declare(strict_types=1);

namespace Atannex\Detector\Tests\Unit;

use Atannex\Detector\Parsers\NativeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UserAgentTest extends TestCase
{
    /**
     * [user agent, type, brand, model, os, os version, browser, browser version, engine]
     *
     * @return array<string, list<?string>>
     */
    public static function agents(): array
    {
        return [
            'Chrome on Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', 'desktop', null, null, 'Windows', '10', 'Chrome', '124.0.0.0', 'Blink'],
            'Edge on Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Edg/124.0.2478.80', 'desktop', null, null, 'Windows', '10', 'Microsoft Edge', '124.0.2478.80', 'Blink'],
            'Legacy Edge' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19582', 'desktop', null, null, 'Windows', '10', 'Microsoft Edge', '18.19582', 'EdgeHTML'],
            'Firefox on Windows 7' => ['Mozilla/5.0 (Windows NT 6.1; WOW64; rv:115.0) Gecko/20100101 Firefox/115.0', 'desktop', null, null, 'Windows', '7', 'Firefox', '115.0', 'Gecko'],
            'IE 11' => ['Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko', 'desktop', null, null, 'Windows', '8.1', 'Internet Explorer', '11.0', 'Trident'],
            'IE 6 on XP' => ['Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)', 'desktop', null, null, 'Windows', 'XP', 'Internet Explorer', '6.0', 'Trident'],
            'Opera on Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36 OPR/108.0.0.0', 'desktop', null, null, 'Windows', '10', 'Opera', '108.0.0.0', 'Blink'],
            'Yandex' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 YaBrowser/24.4.0.0 Safari/537.36', 'desktop', null, null, 'Windows', '10', 'Yandex Browser', '24.4.0.0', 'Blink'],
            'Vivaldi on Linux' => ['Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36 Vivaldi/6.6.3271.55', 'desktop', null, null, 'Linux', null, 'Vivaldi', '6.6.3271.55', 'Blink'],
            'Firefox on Ubuntu' => ['Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:125.0) Gecko/20100101 Firefox/125.0', 'desktop', null, null, 'Ubuntu', null, 'Firefox', '125.0', 'Gecko'],
            'Safari on Mac' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Safari/605.1.15', 'desktop', 'Apple', 'Mac', 'macOS', '10.15.7', 'Safari', '17.4.1', 'WebKit'],
            'Chrome on Mac' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', 'desktop', 'Apple', 'Mac', 'macOS', '10.15.7', 'Chrome', '124.0.0.0', 'Blink'],
            'ChromeOS' => ['Mozilla/5.0 (X11; CrOS x86_64 14541.0.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', 'desktop', null, null, 'ChromeOS', '14541.0.0', 'Chrome', '124.0.0.0', 'Blink'],
            'FreeBSD' => ['Mozilla/5.0 (X11; FreeBSD amd64; rv:122.0) Gecko/20100101 Firefox/122.0', 'desktop', null, null, 'FreeBSD', null, 'Firefox', '122.0', 'Gecko'],

            'Safari on iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Mobile/15E148 Safari/604.1', 'smartphone', 'Apple', 'iPhone', 'iOS', '17.4.1', 'Safari', '17.4.1', 'WebKit'],
            'Chrome on iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/124.0.6367.88 Mobile/15E148 Safari/604.1', 'smartphone', 'Apple', 'iPhone', 'iOS', '17.4', 'Chrome', '124.0.6367.88', 'WebKit'],
            'Firefox on iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/125.0 Mobile/15E148 Safari/605.1.15', 'smartphone', 'Apple', 'iPhone', 'iOS', '17.4', 'Firefox', '125.0', 'WebKit'],
            'Edge on iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 EdgiOS/124.2478.71 Mobile/15E148 Safari/605.1.15', 'smartphone', 'Apple', 'iPhone', 'iOS', '17.4', 'Microsoft Edge', '124.2478.71', 'WebKit'],
            'iPad' => ['Mozilla/5.0 (iPad; CPU OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'tablet', 'Apple', 'iPad', 'iPadOS', '16.6', 'Safari', '16.6', 'WebKit'],
            'iPod' => ['Mozilla/5.0 (iPod touch; CPU iPhone OS 15_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6 Mobile/15E148 Safari/604.1', 'media-player', 'Apple', 'iPod touch', 'iOS', '15.7', 'Safari', '15.6', 'WebKit'],

            'Chrome reduced Android' => ['Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36', 'smartphone', null, null, 'Android', '10', 'Chrome', '124.0.0.0', 'Blink'],
            'Samsung Internet' => ['Mozilla/5.0 (Linux; Android 13; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/23.0 Chrome/115.0.0.0 Mobile Safari/537.36', 'smartphone', 'Samsung', 'SM-S918B', 'Android', '13', 'Samsung Internet', '23.0', 'Blink'],
            'Galaxy Tab' => ['Mozilla/5.0 (Linux; Android 13; SM-X710) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', 'tablet', 'Samsung', 'SM-X710', 'Android', '13', 'Chrome', '124.0.0.0', 'Blink'],
            'Pixel' => ['Mozilla/5.0 (Linux; Android 14; Pixel 8 Pro) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36', 'smartphone', 'Google', 'Pixel 8 Pro', 'Android', '14', 'Chrome', '124.0.0.0', 'Blink'],
            'Tecno WebView' => ['Mozilla/5.0 (Linux; Android 12; TECNO KI7 Build/SP1A.210812.016; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/120.0.6099.193 Mobile Safari/537.36', 'smartphone', 'Tecno', 'KI7', 'Android', '12', 'Chrome WebView', '120.0.6099.193', 'Blink'],
            'Infinix' => ['Mozilla/5.0 (Linux; Android 13; Infinix X6833B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Mobile Safari/537.36', 'smartphone', 'Infinix', 'X6833B', 'Android', '13', 'Chrome', '119.0.0.0', 'Blink'],
            'itel' => ['Mozilla/5.0 (Linux; Android 11; itel A571W) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Mobile Safari/537.36', 'smartphone', 'itel', 'A571W', 'Android', '11', 'Chrome', '110.0.0.0', 'Blink'],
            'Xiaomi code' => ['Mozilla/5.0 (Linux; Android 13; 23021RAAEG) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36', 'smartphone', 'Xiaomi', '23021RAAEG', 'Android', '13', 'Chrome', '120.0.0.0', 'Blink'],
            'Redmi' => ['Mozilla/5.0 (Linux; Android 12; Redmi Note 11) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36', 'smartphone', 'Xiaomi', 'Redmi Note 11', 'Android', '12', 'Chrome', '120.0.0.0', 'Blink'],
            'MIUI Browser' => ['Mozilla/5.0 (Linux; U; Android 12; en-us; M2101K6G Build/SKQ1.210908.001) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/112.0.5615.136 Mobile Safari/537.36 XiaoMi/MiuiBrowser/14.4.0-g', 'smartphone', 'Xiaomi', 'M2101K6G', 'Android', '12', 'MIUI Browser', '14.4.0', 'Blink'],
            'Huawei' => ['Mozilla/5.0 (Linux; Android 10; ELS-NX9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Mobile Safari/537.36', 'smartphone', 'Huawei', 'ELS-NX9', 'Android', '10', 'Chrome', '114.0.0.0', 'Blink'],
            'OnePlus' => ['Mozilla/5.0 (Linux; Android 13; KB2003) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36', 'smartphone', 'OnePlus', 'KB2003', 'Android', '13', 'Chrome', '120.0.0.0', 'Blink'],
            'realme' => ['Mozilla/5.0 (Linux; Android 13; RMX3630) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36', 'smartphone', 'realme', 'RMX3630', 'Android', '13', 'Chrome', '120.0.0.0', 'Blink'],
            'OPPO' => ['Mozilla/5.0 (Linux; Android 13; CPH2591) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36', 'smartphone', 'OPPO', 'CPH2591', 'Android', '13', 'Chrome', '120.0.0.0', 'Blink'],
            'vivo' => ['Mozilla/5.0 (Linux; Android 13; V2207) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36', 'smartphone', 'vivo', 'V2207', 'Android', '13', 'Chrome', '120.0.0.0', 'Blink'],
            'Motorola' => ['Mozilla/5.0 (Linux; Android 12; moto g(60)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36', 'smartphone', 'Motorola', 'moto g(60)', 'Android', '12', 'Chrome', '120.0.0.0', 'Blink'],
            'Firefox on Android' => ['Mozilla/5.0 (Android 14; Mobile; rv:125.0) Gecko/125.0 Firefox/125.0', 'smartphone', null, null, 'Android', '14', 'Firefox', '125.0', 'Gecko'],
            'Opera Mini Android' => ['Opera/9.80 (Android; Opera Mini/36.2.2254/119.132; U; id) Presto/2.12.423 Version/12.16', 'smartphone', null, null, 'Android', null, 'Opera Mini', '36.2.2254', 'Presto'],
            'UC Browser' => ['Mozilla/5.0 (Linux; U; Android 10; en-US; RMX2185 Build/QP1A.190711.020) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/78.0.3904.108 UCBrowser/13.4.0.1306 Mobile Safari/537.36', 'smartphone', 'realme', 'RMX2185', 'Android', '10', 'UC Browser', '13.4.0.1306', 'Blink'],
            'Kindle Fire' => ['Mozilla/5.0 (Linux; Android 9; KFTRWI) AppleWebKit/537.36 (KHTML, like Gecko) Silk/124.3.1 like Chrome/124.0.6367.219 Safari/537.36', 'tablet', 'Amazon', 'KFTRWI', 'Fire OS', '7', 'Amazon Silk', '124.3.1', 'Blink'],
            'HarmonyOS' => ['Mozilla/5.0 (Phone; OpenHarmony 4.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36 ArkWeb/4.1.6.1 Mobile HuaweiBrowser/5.0.4.300', 'smartphone', null, null, 'HarmonyOS', '4.1', 'Huawei Browser', '5.0.4.300', 'Blink'],

            'KaiOS' => ['Mozilla/5.0 (Mobile; Nokia_8110_4G; rv:48.0) Gecko/48.0 Firefox/48.0 KAIOS/2.5', 'feature-phone', 'Nokia', '8110 4G', 'KaiOS', '2.5', 'KaiOS Browser', '48.0', 'Gecko'],
            'Windows Phone' => ['Mozilla/5.0 (Windows Phone 10.0; Android 6.0.1; Microsoft; Lumia 950) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Mobile Safari/537.36 Edge/15.15063', 'smartphone', 'Microsoft', 'Lumia 950', 'Windows Phone', '10.0', 'Microsoft Edge', '15.15063', 'EdgeHTML'],
            'BlackBerry 10' => ['Mozilla/5.0 (BB10; Touch) AppleWebKit/537.35+ (KHTML, like Gecko) Version/10.3.3.2205 Mobile Safari/537.35+', 'smartphone', 'BlackBerry', null, 'BlackBerry OS', null, 'BlackBerry Browser', '10.3.3.2205', 'WebKit'],

            'Samsung TV' => ['Mozilla/5.0 (SMART-TV; Linux; Tizen 6.0) AppleWebKit/538.1 (KHTML, like Gecko) Version/6.0 TV Safari/538.1', 'tv', 'Samsung', null, 'Tizen', '6.0', 'Samsung TV Browser', null, 'WebKit'],
            'LG webOS TV' => ['Mozilla/5.0 (Web0S; Linux/SmartTV) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.79 Safari/537.36 WebAppManager', 'tv', 'LG', null, 'webOS', null, 'Chrome', '79.0.3945.79', 'Blink'],
            'Android TV' => ['Mozilla/5.0 (Linux; Android 11; BRAVIA 4K VH2 Build/RTT2.220118.001; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/120.0.6099.230 Mobile Safari/537.36', 'tv', 'Sony', 'BRAVIA 4K VH2', 'Android TV', '11', 'Chrome WebView', '120.0.6099.230', 'Blink'],
            'Fire TV' => ['Mozilla/5.0 (Linux; Android 9; AFTKA Build/PS7633.3445N; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/120.0.6099.230 Mobile Safari/537.36', 'tv', 'Amazon', 'AFTKA', 'Fire OS', '7', 'Chrome WebView', '120.0.6099.230', 'Blink'],
            'Chromecast' => ['Mozilla/5.0 (X11; Linux armv7l) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36 CrKey/1.56.500000 DeviceType/AndroidTV', 'tv', 'Google', null, 'Linux', null, 'Chrome', '114.0.0.0', 'Blink'],
            'Roku' => ['Roku/DVP-9.10 (519.10E04111A)', 'tv', 'Roku', null, 'Roku OS', '9.10', null, null, null],
            'PlayStation 5' => ['Mozilla/5.0 (PlayStation; PlayStation 5/2.26) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0 Safari/605.1.15', 'console', 'Sony', null, 'PlayStation', '5', 'PlayStation Browser', '13.0', 'WebKit'],
            'Xbox' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64; Xbox; Xbox Series X) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.82 Safari/537.36 Edge/20.02', 'console', 'Microsoft', null, 'Xbox', null, 'Microsoft Edge', '20.02', 'EdgeHTML'],
            'Nintendo Switch' => ['Mozilla/5.0 (Nintendo Switch; WifiWebAuthApplet) AppleWebKit/606.4 (KHTML, like Gecko) NF/6.0.1.15.4 NintendoBrowser/5.1.0.20393', 'console', 'Nintendo', null, 'Nintendo', null, 'Nintendo Browser', '5.1.0.20393', 'WebKit'],
            'Meta Quest' => ['Mozilla/5.0 (Linux; Android 12; Quest 3) AppleWebKit/537.36 (KHTML, like Gecko) OculusBrowser/31.0.0.6.66 SamsungBrowser/4.0 Chrome/120.0.6099.193 VR Safari/537.36', 'xr', 'Meta', 'Quest 3', 'Android', '12', 'Meta Quest Browser', '31.0.0.6.66', 'Blink'],
            'Tesla' => ['Mozilla/5.0 (X11; GNU/Linux) AppleWebKit/537.36 (KHTML, like Gecko) Chromium/79.0.3945.130 Chrome/79.0.3945.130 Safari/537.36 Tesla/2024.14.9-6c2a1a9c5e2b', 'car', 'Tesla', null, 'Linux', null, 'Chromium', '79.0.3945.130', 'Blink'],
            'Kindle e-reader' => ['Mozilla/5.0 (X11; U; Linux armv7l like Android; en-us) AppleWebKit/531.2+ (KHTML, like Gecko) Version/5.0 Safari/531.2+ Kindle/3.0+', 'e-reader', 'Amazon', null, 'Linux', null, 'Safari', '5.0', 'WebKit'],
        ];
    }

    #[DataProvider('agents')]
    public function test_it_parses_user_agents(string $ua, string $type, ?string $brand, ?string $model, ?string $os, ?string $osVersion, ?string $browser, ?string $browserVersion, ?string $engine): void
    {
        $device = (new NativeParser)->parse($ua);

        $this->assertSame(
            compact('type', 'brand', 'model', 'os', 'osVersion', 'browser', 'browserVersion', 'engine'),
            [
                'type' => $device->type->value,
                'brand' => $device->brand,
                'model' => $device->model,
                'os' => $device->os->name,
                'osVersion' => $device->os->version->jsonSerialize(),
                'browser' => $device->browser->name,
                'browserVersion' => $device->browser->version->jsonSerialize(),
                'engine' => $device->engine->name,
            ],
        );

        $this->assertNull($device->bot, "{$ua} is not a bot");
    }
}
