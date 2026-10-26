<?php

declare(strict_types=1);

namespace Atannex\Detector\Tests\Feature;

use Atannex\Detector\Enums\DeviceType;
use Atannex\Detector\Events\NewDeviceDetected;
use Atannex\Detector\Models\UserDevice;
use Atannex\Detector\Tests\Fixtures\Member;
use Atannex\Detector\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

final class TrackingTest extends TestCase
{
    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Mobile/15E148 Safari/604.1';

    private const DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    protected function defineRoutes($router): void
    {
        Route::middleware(['web', 'track-devices'])->get('/home', fn () => 'home');
    }

    public function test_it_records_devices_and_announces_new_ones(): void
    {
        Event::fake([NewDeviceDetected::class]);
        $member = Member::create();

        $response = $this->actingAs($member)->withHeader('User-Agent', self::DESKTOP)->get('/home')->assertOk();
        $cookie = $response->getCookie('device_id');

        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        Event::assertNotDispatched(NewDeviceDetected::class);

        $device = $member->devices()->sole();
        $this->assertSame(DeviceType::Desktop, $device->type);
        $this->assertSame('Chrome on Windows 10', $device->label());
        $this->assertSame('127.0.0.1', $device->ip_address);

        // Same browser again: no new record.
        $this->actingAs($member)->withHeader('User-Agent', self::DESKTOP)->withCookie('device_id', $cookie->getValue())->get('/home');
        $this->assertSame(1, $member->devices()->count());

        // Another one: recorded and announced.
        $this->actingAs($member)->withHeader('User-Agent', self::IPHONE)->withCookie('device_id', '')->get('/home');

        $this->assertSame(2, $member->devices()->count());
        Event::assertDispatched(NewDeviceDetected::class, fn (NewDeviceDetected $e) => $e->user->is($member) && $e->device->isIos() && $e->record->os_name === 'iOS');
    }

    public function test_first_device_can_be_announced(): void
    {
        Event::fake([NewDeviceDetected::class]);
        config(['detector.tracking.notify_first' => true]);

        $this->actingAs(Member::create())->withHeader('User-Agent', self::DESKTOP)->get('/home');

        Event::assertDispatchedTimes(NewDeviceDetected::class, 1);
    }

    public function test_guests_and_bots_are_not_tracked(): void
    {
        $this->withHeader('User-Agent', self::DESKTOP)->get('/home')->assertOk();
        $this->actingAs(Member::create())->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')->get('/home');

        $this->assertSame(0, UserDevice::count());
    }

    public function test_last_seen_is_throttled(): void
    {
        $member = Member::create();
        $this->travelTo(now()->startOfMinute());

        $cookie = $this->actingAs($member)->withHeader('User-Agent', self::DESKTOP)->get('/home')->getCookie('device_id')->getValue();
        $first = $member->devices()->sole()->last_seen_at;

        $this->travel(60)->seconds();
        $this->actingAs($member)->withHeader('User-Agent', self::DESKTOP)->withCookie('device_id', $cookie)->get('/home');
        $this->assertTrue($member->devices()->sole()->last_seen_at->equalTo($first));

        $this->travel(10)->minutes();
        $this->actingAs($member)->withHeader('User-Agent', self::DESKTOP)->withCookie('device_id', $cookie)->get('/home');
        $this->assertTrue($member->devices()->sole()->last_seen_at->greaterThan($first));
    }

    public function test_managing_devices(): void
    {
        $member = Member::create();
        $desktop = $this->actingAs($member)->withHeader('User-Agent', self::DESKTOP)->get('/home')->getCookie('device_id')->getValue();
        $this->actingAs($member)->withHeader('User-Agent', self::IPHONE)->withCookie('device_id', '')->get('/home');

        $request = \Illuminate\Http\Request::create('/', cookies: ['device_id' => $desktop]);

        $current = $member->currentDevice($request);
        $this->assertSame('Chrome on Windows 10', $current->label());
        $this->assertTrue($current->isCurrent($request));
        $this->assertTrue($current->trust()->isTrusted());
        $this->assertSame(1, $member->devices()->trusted()->count());

        $this->assertSame(1, $member->forgetOtherDevices($request));
        $this->assertSame(1, $member->devices()->count());
        $this->assertTrue($member->forgetDevice($current));
        $this->assertNull($member->currentDevice($request));
    }

    public function test_prune_command(): void
    {
        $member = Member::create();
        $this->actingAs($member)->withHeader('User-Agent', self::DESKTOP)->get('/home');

        $this->travel(400)->days();

        $this->artisan('detector:prune')->expectsOutputToContain('Removed 1 device(s)')->assertSuccessful();
        $this->assertSame(0, UserDevice::count());
        $this->artisan('detector:prune', ['--days' => 0])->assertFailed();
    }
}
