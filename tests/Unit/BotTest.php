<?php

declare(strict_types=1);

namespace Atannex\Detector\Tests\Unit;

use Atannex\Detector\Bots\BotVerifier;
use Atannex\Detector\Contracts\Resolver;
use Atannex\Detector\Data\Bot;
use Atannex\Detector\Enums\BotCategory;
use Atannex\Detector\Parsers\NativeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BotTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: ?string}>
     */
    public static function bots(): array
    {
        return [
            'Googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'Googlebot', 'search', 'Google'],
            'Googlebot Smartphone' => ['Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.6367.155 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'Googlebot', 'search', 'Google'],
            'Googlebot-Image' => ['Googlebot-Image/1.0', 'Googlebot-Image', 'search', 'Google'],
            'Bingbot' => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm) Chrome/116.0.1938.76 Safari/537.36', 'Bingbot', 'search', 'Microsoft'],
            'Applebot' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1.1 Safari/605.1.15 (Applebot/0.1; +http://www.apple.com/go/applebot)', 'Applebot', 'search', 'Apple'],
            'YandexBot' => ['Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)', 'YandexBot', 'search', 'Yandex'],
            'Baiduspider' => ['Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)', 'Baiduspider', 'search', 'Baidu'],
            'DuckDuckBot' => ['DuckDuckBot/1.1; (+http://duckduckgo.com/duckduckbot.html)', 'DuckDuckBot', 'search', 'DuckDuckGo'],

            'GPTBot' => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)', 'GPTBot', 'ai-crawler', 'OpenAI'],
            'ChatGPT-User' => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot', 'ChatGPT-User', 'ai-assistant', 'OpenAI'],
            'OAI-SearchBot' => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; OAI-SearchBot/1.0; +https://openai.com/searchbot', 'OAI-SearchBot', 'ai-search', 'OpenAI'],
            'ClaudeBot' => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; ClaudeBot/1.0; +claudebot@anthropic.com)', 'ClaudeBot', 'ai-crawler', 'Anthropic'],
            'Claude-User' => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-User/1.0; +Claude-User@anthropic.com)', 'Claude-User', 'ai-assistant', 'Anthropic'],
            'PerplexityBot' => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)', 'PerplexityBot', 'ai-search', 'Perplexity'],
            'CCBot' => ['CCBot/2.0 (https://commoncrawl.org/faq/)', 'CCBot', 'ai-crawler', 'Common Crawl'],
            'Bytespider' => ['Mozilla/5.0 (Linux; Android 5.0) AppleWebKit/537.36 (KHTML, like Gecko) Mobile Safari/537.36 (compatible; Bytespider; spider-feedback@bytedance.com)', 'Bytespider', 'ai-crawler', 'ByteDance'],
            'Meta-ExternalAgent' => ['meta-externalagent/1.1 (+https://developers.facebook.com/docs/sharing/webmasters/crawler)', 'Meta-ExternalAgent', 'ai-crawler', 'Meta'],
            'Amazonbot' => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Amazonbot/0.1; +https://developer.amazon.com/support/amazonbot) Chrome/119.0.6045.214 Safari/537.36', 'Amazonbot', 'ai-crawler', 'Amazon'],

            'Facebook preview' => ['facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'Facebook', 'social', 'Meta'],
            'Twitterbot' => ['Twitterbot/1.0', 'Twitterbot', 'social', 'X'],
            'WhatsApp preview' => ['WhatsApp/2.23.20.0', 'WhatsApp', 'social', 'Meta'],
            'Slackbot' => ['Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)', 'Slackbot', 'social', 'Slack'],
            'Discordbot' => ['Mozilla/5.0 (compatible; Discordbot/2.0; +https://discordapp.com)', 'Discordbot', 'social', 'Discord'],
            'LinkedInBot' => ['LinkedInBot/1.0 (compatible; Mozilla/5.0; Apache-HttpClient +http://www.linkedin.com)', 'LinkedInBot', 'social', 'LinkedIn'],

            'AhrefsBot' => ['Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', 'AhrefsBot', 'seo', 'Ahrefs'],
            'SemrushBot' => ['Mozilla/5.0 (compatible; SemrushBot/7~bl; +http://www.semrush.com/bot.html)', 'SemrushBot', 'seo', 'Semrush'],
            'UptimeRobot' => ['Mozilla/5.0+(compatible; UptimeRobot/2.0; http://www.uptimerobot.com/)', 'UptimeRobot', 'monitoring', 'UptimeRobot'],
            'Lighthouse' => ['Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse', 'Lighthouse', 'monitoring', 'Google'],
            'Feedly' => ['Feedly/1.0 (+http://www.feedly.com/fetcher.html; 5 subscribers)', 'Feedly', 'feed', 'Feedly'],
            'Internet Archive' => ['Mozilla/5.0 (compatible; archive.org_bot +http://archive.org/details/archive.org_bot)', 'Internet Archive', 'archiver', 'Internet Archive'],
            'Censys' => ['Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'Censys', 'security', 'Censys'],
            'Headless Chrome' => ['Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/124.0.6367.60 Safari/537.36', 'Headless Chrome', 'automation', 'Google'],
            'curl' => ['curl/8.4.0', 'curl', 'http-client', null],
            'python-requests' => ['python-requests/2.31.0', 'Python Requests', 'http-client', null],
            'Go' => ['Go-http-client/2.0', 'Go HTTP client', 'http-client', null],
            'Unknown crawler' => ['Mozilla/5.0 (compatible; AcmeCrawler/1.0; +https://acme.example/crawler)', 'AcmeCrawler', 'other', null],
        ];
    }

    #[DataProvider('bots')]
    public function test_it_recognises_bots(string $ua, string $name, string $category, ?string $producer): void
    {
        $bot = (new NativeParser)->parse($ua)->bot;

        $this->assertNotNull($bot, "{$ua} is a bot");
        $this->assertSame([$name, $category, $producer], [$bot->name, $bot->category->value, $bot->producer]);
    }

    public function test_bots_keep_the_device_they_present(): void
    {
        $device = (new NativeParser)->parse(self::bots()['Googlebot Smartphone'][0]);

        $this->assertTrue($device->isSmartphone());
        $this->assertTrue($device->isSearchEngine());
        $this->assertFalse($device->isHuman());
    }

    public function test_ai_categories(): void
    {
        $parser = new NativeParser;

        $this->assertTrue($parser->parse(self::bots()['GPTBot'][0])->isAiCrawler());
        $this->assertTrue($parser->parse(self::bots()['ChatGPT-User'][0])->isAiBot());
        $this->assertFalse($parser->parse(self::bots()['ChatGPT-User'][0])->isAiCrawler());
        $this->assertTrue($parser->parse(self::bots()['ClaudeBot'][0])->bot->in('ai'));
        $this->assertFalse($parser->parse(self::bots()['Googlebot'][0])->bot->in('ai'));
    }

    public function test_people_and_apps_are_not_bots(): void
    {
        $parser = new NativeParser;

        foreach ([
            'Mozilla/5.0 (Linux; Android 13; CUBOT KINGKONG 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 [Pinterest/iOS]',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Teams/1.6.00.4472 Chrome/98.0.4758.141 Electron/17.4.11 Safari/537.36',
            'okhttp/4.12.0',
            'Dart/3.3 (dart:io)',
            'MyApp/1.0 CFNetwork/1494.0.7 Darwin/23.4.0',
        ] as $ua) {
            $this->assertNull($parser->parse($ua)->bot, $ua);
        }
    }

    public function test_an_empty_user_agent_is_a_bot_unless_configured(): void
    {
        $this->assertSame('Empty user agent', (new NativeParser)->parse('')->bot?->name);
        $this->assertNull((new NativeParser(emptyIsBot: false))->parse('')->bot);
    }

    public function test_verification_by_reverse_and_forward_dns(): void
    {
        $resolver = new class implements Resolver
        {
            public function reverse(string $ip): ?string
            {
                return ['66.249.66.1' => 'crawl-66-249-66-1.googlebot.com', '6.6.6.6' => 'googlebot.com.evil.example', '7.7.7.7' => 'fake.googlebot.com'][$ip] ?? null;
            }

            public function forward(string $host): array
            {
                return ['crawl-66-249-66-1.googlebot.com' => ['66.249.66.1'], 'fake.googlebot.com' => ['1.2.3.4']][$host] ?? [];
            }
        };

        $verifier = new BotVerifier($resolver, ['Googlebot' => ['googlebot.com', 'google.com']]);
        $googlebot = new Bot('Googlebot', BotCategory::Search);

        $this->assertTrue($verifier->verify($googlebot, '66.249.66.1'));
        $this->assertFalse($verifier->verify($googlebot, '6.6.6.6'), 'suffix must be a domain boundary');
        $this->assertFalse($verifier->verify($googlebot, '7.7.7.7'), 'forward lookup must match');
        $this->assertFalse($verifier->verify($googlebot, '8.8.8.8'));
        $this->assertFalse($verifier->verify($googlebot, null));
        $this->assertNull($verifier->verify('GPTBot', '1.1.1.1'), 'nothing to verify against');
    }
}
