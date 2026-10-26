<?php

declare(strict_types=1);

namespace Atannex\Detector\Tests\Unit;

use Atannex\Detector\Device;
use Atannex\Detector\Enums\DeviceType;
use Atannex\Detector\Parsers\NativeParser;
use Atannex\Detector\Version;
use PHPUnit\Framework\TestCase;

final class DeviceTest extends TestCase
{
    public function test_versions_compare(): void
    {
        $version = Version::from('17_4_1');

        $this->assertSame('17.4.1', (string) $version);
        $this->assertSame([17, 4, 1], [$version->major(), $version->minor(), $version->patch()]);
        $this->assertTrue($version->isAtLeast('17'));
        $this->assertTrue($version->isAtLeast('17.4.1'));
        $this->assertFalse($version->isAtLeast('17.5'));
        $this->assertTrue($version->isBelow(18));
        $this->assertTrue($version->is('17'));
        $this->assertTrue($version->is('17.4'));
        $this->assertFalse($version->is('17.40'));
        $this->assertSame('120', Version::from('120.0.0.0')->short());
        $this->assertFalse(Version::none()->isAtLeast('0'));
        $this->assertSame('XP', (string) Version::from('XP'));
    }

    public function test_in_app_browsers(): void
    {
        $parser = new NativeParser;
        $cases = [
            'Instagram' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Instagram 326.0.3.30.91 (iPhone15,3; iOS 17_4; en_US; en; scale=3.00; 1290x2796; 584870453)',
            'Facebook' => 'Mozilla/5.0 (Linux; Android 13; SM-A536B Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/124.0.6367.82 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/462.0.0.47.86;]',
            'Messenger' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 [FBAN/MessengerForiOS;FBAV/455.0.0.36.107;FBBV/593087232]',
            'TikTok' => 'Mozilla/5.0 (Linux; Android 12; TECNO CK7n Build/SP1A.210812.016; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/120.0.6099.193 Mobile Safari/537.36 trill_340204 JsSdk/1.0 NetType/WIFI Channel/googleplay AppName/musical_ly app_version/34.2.4 ByteLocale/en ByteFullLocale/en Region/CM BytedanceWebview/d8a21c6',
            'WeChat' => 'Mozilla/5.0 (Linux; Android 13; V2207 Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/116.0.0.0 Mobile Safari/537.36 XWEB/1160065 MMWEBSDK/20231202 MMWEBID/1234 MicroMessenger/8.0.47.2560(0x28002F30) WeChat/arm64 Weixin NetType/WIFI Language/zh_CN ABI/arm64',
            'LinkedIn' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 [LinkedInApp]/9.29.6750',
            'Google' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GSA/310.0.620826033 Mobile/15E148 Safari/604.1',
            'Android app' => 'okhttp/4.12.0',
            'Flutter app' => 'Dart/3.3 (dart:io)',
        ];

        foreach ($cases as $app => $ua) {
            $device = $parser->parse($ua);

            $this->assertSame($app, $device->app, $ua);
            $this->assertTrue($device->isInApp(), $ua);
        }

        $this->assertSame('Facebook on Android 13', $parser->parse($cases['Facebook'])->label());
        $this->assertTrue($parser->parse($cases['TikTok'])->isWebView());
        $this->assertSame('Tecno', $parser->parse($cases['TikTok'])->brand);
    }

    public function test_is_matches_any_name(): void
    {
        $device = (new NativeParser)->parse('Mozilla/5.0 (Linux; Android 13; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/23.0 Chrome/115.0.0.0 Mobile Safari/537.36');

        $this->assertTrue($device->is('android'));
        $this->assertTrue($device->is('mobile'));
        $this->assertTrue($device->is('smartphone'));
        $this->assertTrue($device->is('samsung'));
        $this->assertTrue($device->is('Samsung Internet'));
        $this->assertTrue($device->is('blink'));
        $this->assertTrue($device->is('ios', 'android'));
        $this->assertFalse($device->is('ios', 'tablet', 'bot'));
        $this->assertTrue($device->browser->isBasedOn('Chrome'));
        $this->assertFalse($device->browser->is('Chrome'));
        $this->assertSame('Samsung SM-S918B', $device->name());
        $this->assertSame('Samsung Internet 23 on Android 13', $device->label());
        $this->assertSame('device-smartphone os-android browser-samsung-internet touch', $device->classes());
    }

    public function test_make_and_with(): void
    {
        $device = Device::make(['type' => 'tablet', 'os' => 'iPadOS 17.4', 'browser' => 'Safari 17.4', 'brand' => 'Apple', 'model' => 'iPad']);

        $this->assertSame(DeviceType::Tablet, $device->type);
        $this->assertSame('apple', $device->os->family);
        $this->assertTrue($device->isIos());
        $this->assertSame('Safari 17.4 on iPadOS 17.4', $device->label());

        $bot = $device->with(bot: Device::make(['bot' => ['name' => 'GPTBot', 'category' => 'ai-crawler']])->bot);

        $this->assertTrue($bot->isAiCrawler());
        $this->assertSame('GPTBot', $bot->label());
        $this->assertSame('tablet', $bot->toArray()['type']);
        $this->assertJson($bot->toJson());

        $this->expectException(\InvalidArgumentException::class);
        $device->with(colour: 'red');
    }
}
