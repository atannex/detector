<?php

declare(strict_types=1);

namespace Atannex\Detector\Tests\Feature;

use Atannex\Detector\Events\BotBlocked;
use Atannex\Detector\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

final class HttpTest extends TestCase
{
    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Mobile/15E148 Safari/604.1';

    private const DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    private const GPTBOT = 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)';

    private const GOOGLEBOT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    protected function defineRoutes($router): void
    {
        Route::middleware('device:mobile,tablet')->get('/handheld', fn () => 'ok');
        Route::middleware('device:!bot')->get('/humans', fn () => 'ok');
        Route::middleware('block-bots')->get('/article', fn () => 'ok');
        Route::middleware('block-bots:ai,seo')->get('/strict', fn () => 'ok');
        Route::middleware('block-bots:*')->get('/private', fn () => 'ok');
        Route::middleware('web')->get('/page', fn () => '<html>page</html>');
        Route::middleware('web')->get('/api', fn () => response()->json([]));
    }

    public function test_device_middleware(): void
    {
        $this->withHeader('User-Agent', self::IPHONE)->get('/handheld')->assertOk();
        $this->withHeader('User-Agent', self::DESKTOP)->get('/handheld')->assertNotFound();
        $this->withHeader('User-Agent', self::DESKTOP)->get('/humans')->assertOk();
        $this->withHeader('User-Agent', self::GOOGLEBOT)->get('/humans')->assertNotFound();
    }

    public function test_block_bots_defaults_to_ai_crawlers(): void
    {
        Event::fake([BotBlocked::class]);

        $this->withHeader('User-Agent', self::GPTBOT)->get('/article')->assertForbidden();
        $this->withHeader('User-Agent', self::GOOGLEBOT)->get('/article')->assertOk();
        $this->withHeader('User-Agent', self::IPHONE)->get('/article')->assertOk();

        Event::assertDispatched(BotBlocked::class, fn (BotBlocked $event) => $event->bot->name === 'GPTBot');
    }

    public function test_block_bots_with_categories_names_and_allow_list(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)')->get('/strict')->assertForbidden();
        $this->withHeader('User-Agent', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot')->get('/strict')->assertForbidden();
        $this->withHeader('User-Agent', self::GOOGLEBOT)->get('/strict')->assertOk();
        $this->withHeader('User-Agent', self::GOOGLEBOT)->get('/private')->assertForbidden();

        config(['detector.bots.block.allow' => ['Googlebot']]);
        $this->withHeader('User-Agent', self::GOOGLEBOT)->get('/private')->assertOk();
    }

    public function test_client_hints_are_requested_on_html_pages(): void
    {
        $response = $this->get('/page')->assertOk();

        $this->assertStringContainsString('Sec-CH-UA-Platform-Version', (string) $response->headers->get('Accept-CH'));
        $this->assertStringContainsString('Sec-CH-UA-Model', (string) $response->headers->get('Accept-CH'));
        $this->assertFalse($response->headers->has('Critical-CH'));

        $this->assertFalse($this->get('/api')->headers->has('Accept-CH'));
    }

    public function test_critical_hints(): void
    {
        config(['detector.client_hints.critical' => ['Sec-CH-UA-Model']]);

        $this->assertSame('Sec-CH-UA-Model', $this->get('/page')->headers->get('Critical-CH'));
    }

    public function test_blade_directives(): void
    {
        $view = <<<'BLADE'
        @mobile phone @endmobile
        @desktop desk @enddesktop
        @device('ios') ios @enddevice
        @browser('safari') safari @endbrowser
        @bot nobody @endbot
        <body class="@deviceClasses">
        BLADE;

        $this->withHeader('User-Agent', self::IPHONE)->get('/page');
        $html = Blade::render($view);

        $this->assertStringContainsString('phone', $html);
        $this->assertStringContainsString('ios', $html);
        $this->assertStringContainsString('safari', $html);
        $this->assertStringNotContainsString('desk', $html);
        $this->assertStringNotContainsString('nobody', $html);
        $this->assertStringContainsString('class="device-smartphone os-ios browser-safari touch"', $html);
    }

    public function test_bot_directive_with_categories(): void
    {
        $this->withHeader('User-Agent', self::GPTBOT)->get('/page');

        $this->assertSame('ai', trim(Blade::render("@bot('ai') ai @endbot\n@bot('search') search @endbot")));
    }

    public function test_probe_script(): void
    {
        $html = Blade::render('@deviceProbe');

        $this->assertStringStartsWith('<script>', $html);
        $this->assertStringContainsString('"device_probe"', $html);
        $this->assertStringContainsString('maxTouchPoints', $html);

        config(['detector.probe.enabled' => false]);
        $this->assertSame('', Blade::render('@deviceProbe'));
    }
}
