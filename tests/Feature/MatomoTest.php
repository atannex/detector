<?php

declare(strict_types=1);

namespace Atannex\Detector\Tests\Feature;

use Atannex\Detector\Facades\Detector;
use Atannex\Detector\Tests\TestCase;
use DeviceDetector\DeviceDetector;

final class MatomoTest extends TestCase
{
    protected array $detector = ['driver' => 'matomo'];

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(DeviceDetector::class)) {
            $this->markTestSkipped('matomo/device-detector is not installed.');
        }
    }

    public function test_matomo_adds_marketing_names_and_keeps_native_strengths(): void
    {
        $device = Detector::parse(
            'Mozilla/5.0 (Linux; Android 13; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/23.0 Chrome/115.0.0.0 Mobile Safari/537.36',
        );

        $this->assertSame('Samsung', $device->brand);
        $this->assertSame('Galaxy S23 Ultra', $device->model);
        $this->assertSame('Samsung Internet', $device->browser->name);
        $this->assertContains('matomo', $device->sources);
    }

    public function test_bots_keep_native_categories(): void
    {
        $device = Detector::parse('Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)');

        $this->assertTrue($device->isAiCrawler());
    }

    public function test_hints_reach_matomo(): void
    {
        $device = Detector::parse(
            'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36',
            ['Sec-CH-UA-Model' => '"Pixel 8 Pro"', 'Sec-CH-UA-Platform' => '"Android"', 'Sec-CH-UA-Platform-Version' => '"14.0.0"', 'Sec-CH-UA-Mobile' => '?1'],
        );

        $this->assertSame('Google', $device->brand);
        $this->assertSame('Pixel 8 Pro', $device->model);
        $this->assertSame('14.0.0', (string) $device->os->version);
    }
}
