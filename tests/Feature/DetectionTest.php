<?php

declare(strict_types=1);

namespace Atannex\Detector\Tests\Feature;

use Atannex\Detector\ClientHints;
use Atannex\Detector\Contracts\Parser;
use Atannex\Detector\Contracts\Resolver;
use Atannex\Detector\Data\Probe;
use Atannex\Detector\Detector as DetectorService;
use Atannex\Detector\Device;
use Atannex\Detector\Exceptions\InvalidConfiguration;
use Atannex\Detector\Facades\Detector;
use Atannex\Detector\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

final class DetectionTest extends TestCase
{
    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Mobile/15E148 Safari/604.1';

    private const MAC = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15';

    protected function defineRoutes($router): void
    {
        Route::middleware('web')->get('/device', fn (Request $request) => $request->device()->toArray());
        Route::get('/helper', fn () => ['label' => device()->label(), 'mobile' => Detector::isMobile()]);
    }

    public function test_the_request_macro_reads_user_agent_hints_and_probe(): void
    {
        $this->withHeaders([
            'User-Agent' => self::MAC,
            'Sec-CH-UA-Platform' => '"macOS"',
        ])->withUnencryptedCookie('device_probe', 't=5|w=1024|h=1366|r=2|s=1')
            ->get('/device')
            ->assertOk()
            ->assertJsonPath('type', 'tablet')
            ->assertJsonPath('os.name', 'iPadOS')
            ->assertJsonPath('probe.standalone', true)
            ->assertJsonPath('sources', ['user-agent', 'client-hints', 'probe']);
    }

    public function test_the_helper_and_facade_proxy_the_current_device(): void
    {
        $this->withHeader('User-Agent', self::IPHONE)->get('/helper')
            ->assertJson(['label' => 'Safari 17.4.1 on iOS 17.4.1', 'mobile' => true]);
    }

    public function test_detection_is_memoised_on_the_request(): void
    {
        $request = Request::create('/', server: ['HTTP_USER_AGENT' => self::IPHONE]);
        $detector = $this->app->make(DetectorService::class);

        $this->assertSame($detector->detect($request), $detector->detect($request));
        $this->assertSame($detector->parse(self::IPHONE), $detector->parse(self::IPHONE), 'in-memory cache');
    }

    public function test_parse_accepts_header_arrays_and_probe_strings(): void
    {
        $device = Detector::parse(self::MAC, ['Sec-CH-UA-Platform' => '"macOS"', 'Sec-CH-UA-Platform-Version' => '"14.4.1"'], 't=0');

        $this->assertSame('macOS 14.4.1', $device->os->label());
        $this->assertFalse($device->isTouch());
    }

    public function test_a_cache_store_keeps_results(): void
    {
        config(['detector.cache.store' => 'array']);
        $this->app->forgetInstance(DetectorService::class);
        Detector::clearResolvedInstances();

        Detector::parse(self::IPHONE);

        $key = 'detector:'.md5(self::IPHONE."\0\0");
        $this->assertInstanceOf(Device::class, Cache::store('array')->get($key));
    }

    public function test_faking(): void
    {
        $fake = Detector::fake(['type' => 'tablet', 'os' => 'Android 14', 'brand' => 'Samsung']);

        $this->get('/helper')->assertJson(['mobile' => false]);
        $this->assertTrue(Detector::isTablet());
        $this->assertSame($fake, device());
        $this->assertTrue(Detector::isFaking());

        Detector::fake(self::IPHONE);
        $this->assertTrue(Detector::isIos());

        Detector::stopFaking();
        $this->assertFalse(Detector::isFaking());
    }

    public function test_custom_drivers(): void
    {
        $parser = new class implements Parser
        {
            public function parse(string $userAgent, ClientHints $hints, ?Probe $probe = null): Device
            {
                return Device::make(['type' => 'console', 'sources' => ['mine']]);
            }
        };

        Detector::extend('mine', fn () => $parser);
        config(['detector.driver' => 'mine']);
        $this->app->forgetInstance(DetectorService::class);
        Detector::clearResolvedInstances();
        Detector::extend('mine', fn () => $parser);

        $this->assertTrue(Detector::parse('anything')->isConsole());

        Detector::usingParser(new \Atannex\Detector\Parsers\NativeParser);
        $this->assertTrue(Detector::parse(self::IPHONE)->isIos());
    }

    public function test_unknown_drivers_fail_loudly(): void
    {
        config(['detector.driver' => 'nope']);
        $this->app->forgetInstance(DetectorService::class);
        Detector::clearResolvedInstances();

        $this->expectException(InvalidConfiguration::class);
        Detector::parse(self::IPHONE);
    }

    public function test_verify_bot_uses_the_resolver(): void
    {
        $this->app->instance(Resolver::class, new class implements Resolver
        {
            public function reverse(string $ip): ?string
            {
                return $ip === '66.249.66.1' ? 'crawl-66-249-66-1.googlebot.com' : null;
            }

            public function forward(string $host): array
            {
                return ['66.249.66.1'];
            }
        });

        $real = Request::create('/', server: ['HTTP_USER_AGENT' => 'Googlebot/2.1 (+http://www.google.com/bot.html)', 'REMOTE_ADDR' => '66.249.66.1']);
        $fake = Request::create('/', server: ['HTTP_USER_AGENT' => 'Googlebot/2.1 (+http://www.google.com/bot.html)', 'REMOTE_ADDR' => '5.6.7.8']);
        $human = Request::create('/', server: ['HTTP_USER_AGENT' => self::IPHONE]);

        $this->assertTrue(Detector::verifyBot($real));
        $this->assertFalse(Detector::verifyBot($fake));
        $this->assertNull(Detector::verifyBot($human));
    }

    public function test_parse_command(): void
    {
        $this->artisan('detector:parse', ['user-agent' => self::IPHONE])
            ->expectsOutputToContain('Safari 17.4.1 on iOS 17.4.1')
            ->assertSuccessful();

        $this->artisan('detector:parse', [
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            '--hint' => ['Sec-CH-UA-Platform: "Windows"', 'Sec-CH-UA-Platform-Version: "15.0.0"'],
            '--json' => true,
        ])->expectsOutputToContain('"version": "11"')->assertSuccessful();
    }
}
