<?php

declare(strict_types=1);

namespace Atannex\Detector\Tests\Unit;

use Atannex\Detector\ClientHints;
use Atannex\Detector\Data\Probe;
use Atannex\Detector\Parsers\NativeParser;
use PHPUnit\Framework\TestCase;

final class ClientHintsTest extends TestCase
{
    private const CHROME_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    private const CHROME_ANDROID = 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36';

    public function test_it_parses_structured_headers_and_drops_grease(): void
    {
        $hints = ClientHints::fromHeaders([
            'sec-ch-ua' => '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"',
            'Sec-CH-UA-Full-Version-List' => '"Chromium";v="124.0.6367.91", "Google Chrome";v="124.0.6367.91", "Not-A.Brand";v="99.0.0.0"',
            'Sec-CH-UA-Mobile' => '?0',
            'Sec-CH-UA-Platform' => '"Windows"',
            'Sec-CH-UA-Platform-Version' => '"15.0.0"',
            'Sec-CH-UA-Model' => '""',
            'HTTP_SEC_CH_UA_ARCH' => '"x86"',
            'Sec-CH-UA-Bitness' => '"64"',
            'Sec-CH-UA-Form-Factors' => '"Desktop"',
        ]);

        $this->assertSame(['Chromium' => '124', 'Google Chrome' => '124'], $hints->brands);
        $this->assertSame(['Google Chrome', '124.0.6367.91'], $hints->brand());
        $this->assertFalse($hints->mobile);
        $this->assertSame('Windows', $hints->platform);
        $this->assertSame('15.0.0', $hints->platformVersion);
        $this->assertNull($hints->model);
        $this->assertSame('x86', $hints->architecture);
        $this->assertSame(['Desktop'], $hints->formFactors);
        $this->assertEquals($hints, ClientHints::fromHeaders($hints->toHeaders()));
    }

    public function test_windows_11_and_full_versions_come_from_hints(): void
    {
        $device = (new NativeParser)->parse(self::CHROME_WINDOWS, ClientHints::fromHeaders([
            'Sec-CH-UA' => '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"',
            'Sec-CH-UA-Full-Version-List' => '"Chromium";v="124.0.6367.91", "Google Chrome";v="124.0.6367.91"',
            'Sec-CH-UA-Platform' => '"Windows"',
            'Sec-CH-UA-Platform-Version' => '"15.0.0"',
            'Sec-CH-UA-Arch' => '"arm"',
            'Sec-CH-UA-Bitness' => '"64"',
        ]));

        $this->assertSame('Windows 11', $device->os->label());
        $this->assertSame('124.0.6367.91', (string) $device->browser->version);
        $this->assertSame('124.0.6367.91', (string) $device->engine->version);
        $this->assertSame(['arm', 64], [$device->architecture, $device->bitness]);
        $this->assertSame(['user-agent', 'client-hints'], $device->sources);
    }

    public function test_windows_platform_versions(): void
    {
        $parser = new NativeParser;
        $os = fn (string $version) => $parser->parse(self::CHROME_WINDOWS, ClientHints::fromHeaders(['Sec-CH-UA-Platform' => '"Windows"', 'Sec-CH-UA-Platform-Version' => "\"{$version}\""]))->os->label();

        $this->assertSame('Windows 11', $os('13.0.0'));
        $this->assertSame('Windows 10', $os('10.0.0'));
        $this->assertSame('Windows 8.1', $os('0.3.0'));
        $this->assertSame('Windows 7', $os('0.1.0'));
    }

    public function test_hints_reveal_the_android_model_and_real_browser(): void
    {
        $device = (new NativeParser)->parse(self::CHROME_ANDROID, ClientHints::fromHeaders([
            'Sec-CH-UA' => '"Chromium";v="124", "Brave";v="124", "Not-A.Brand";v="99"',
            'Sec-CH-UA-Mobile' => '?1',
            'Sec-CH-UA-Platform' => '"Android"',
            'Sec-CH-UA-Platform-Version' => '"14.0.0"',
            'Sec-CH-UA-Model' => '"SM-S928B"',
        ]));

        $this->assertSame(['Samsung', 'SM-S928B'], [$device->brand, $device->model]);
        $this->assertSame('Android 14', $device->os->label());
        $this->assertSame('14.0.0', (string) $device->os->version);
        $this->assertSame('Brave', $device->browser->name);
        $this->assertTrue($device->isSmartphone());
    }

    public function test_form_factors_decide_the_type(): void
    {
        $parser = new NativeParser;

        $this->assertTrue($parser->parse(self::CHROME_ANDROID, ClientHints::fromHeaders(['Sec-CH-UA-Form-Factors' => '"Tablet"']))->isTablet());
        $this->assertTrue($parser->parse(self::CHROME_WINDOWS, ClientHints::fromHeaders(['Sec-CH-UA-Form-Factors' => '"Desktop", "XR"']))->isXr());
    }

    public function test_android_webview_brand(): void
    {
        $device = (new NativeParser)->parse(self::CHROME_ANDROID, ClientHints::fromHeaders(['Sec-CH-UA' => '"Android WebView";v="124", "Chromium";v="124"']));

        $this->assertSame('Chrome WebView', $device->browser->name);
        $this->assertTrue($device->isWebView());
    }

    public function test_a_more_specific_user_agent_browser_wins_over_hints(): void
    {
        $ua = 'Mozilla/5.0 (Linux; Android 13; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/23.0 Chrome/115.0.0.0 Mobile Safari/537.36';

        $this->assertSame('Samsung Internet', (new NativeParser)->parse($ua, ClientHints::fromHeaders(['Sec-CH-UA' => '"Chromium";v="115", "Not/A)Brand";v="99"']))->browser->name);
    }

    public function test_the_probe_unmasks_an_ipad_asking_for_desktop_sites(): void
    {
        $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15';
        $parser = new NativeParser;

        $this->assertTrue($parser->parse($ua, new ClientHints)->isDesktop());

        $device = $parser->parse($ua, new ClientHints, Probe::parse('t=5|w=1024|h=1366|r=2|s=0'));

        $this->assertTrue($device->isTablet());
        $this->assertSame('iPadOS 17.4', $device->os->label());
        $this->assertSame('iPad', $device->model);
        $this->assertTrue($device->isTouch());
        $this->assertSame(['user-agent', 'probe'], $device->sources);
    }

    public function test_probe_parsing_is_defensive(): void
    {
        $probe = Probe::parse('t=5|w=390|h=844|r=3|s=1');

        $this->assertSame([5, 390, 844, 3.0, true], [$probe->touchPoints, $probe->width, $probe->height, $probe->pixelRatio, $probe->standalone]);
        $this->assertNull(Probe::parse(''));
        $this->assertNull(Probe::parse('garbage'));
        $this->assertNull(Probe::parse(str_repeat('t=1|', 100)));
        $this->assertNull(Probe::parse('t=9999|w=-5')->touchPoints);
    }
}
