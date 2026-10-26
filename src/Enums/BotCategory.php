<?php

declare(strict_types=1);

namespace Atannex\Detector\Enums;

enum BotCategory: string
{
    /** Search engine indexers: Googlebot, Bingbot, YandexBot… */
    case Search = 'search';
    /** Crawlers collecting training data: GPTBot, ClaudeBot, CCBot, Bytespider… */
    case AiCrawler = 'ai-crawler';
    /** AI search indexes: OAI-SearchBot, Claude-SearchBot, PerplexityBot… */
    case AiSearch = 'ai-search';
    /** Fetches made for a person talking to an assistant: ChatGPT-User, Claude-User… */
    case AiAssistant = 'ai-assistant';
    /** Link previews: facebookexternalhit, Twitterbot, Slackbot, WhatsApp… */
    case Social = 'social';
    case Seo = 'seo';
    case Monitoring = 'monitoring';
    case Feed = 'feed';
    case Archiver = 'archiver';
    case Advertising = 'advertising';
    /** Scanners: Censys, Nuclei, zgrab, sqlmap… */
    case Security = 'security';
    /** Headless and automated browsers: HeadlessChrome, PhantomJS, Playwright… */
    case Automation = 'automation';
    /** Libraries and command-line tools: curl, python-requests, Go-http-client… */
    case HttpClient = 'http-client';
    case Other = 'other';

    /** Any AI fetcher: training, search or on behalf of a user. */
    public function isAi(): bool
    {
        return in_array($this, [self::AiCrawler, self::AiSearch, self::AiAssistant], true);
    }

    public static function fromName(string $name): ?self
    {
        $name = strtolower(trim($name));

        return self::tryFrom($name) ?? match ($name) {
            'ai' => null,
            'crawler', 'search-engine', 'search engine' => self::Search,
            'preview', 'social-preview', 'link-preview' => self::Social,
            'library', 'http', 'tool', 'cli' => self::HttpClient,
            'headless', 'scraper' => self::Automation,
            'scanner' => self::Security,
            default => null,
        };
    }
}
