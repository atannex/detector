<?php

declare(strict_types=1);

namespace Atannex\Detector\Http\Middleware;

use Atannex\Detector\Detector;
use Atannex\Detector\Events\BotBlocked;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns away bots by category or name: ->middleware('block-bots') uses
 * detector.bots.block (AI training crawlers by default), or list them:
 * 'block-bots:ai', 'block-bots:ai-crawler,seo,GPTBot', 'block-bots:*'.
 * Bots named in 'allow' always pass. Alias: 'block-bots'.
 *
 * robots.txt asks; this enforces. A crawler that lies about its
 * User-Agent is not caught here.
 */
final class BlockBots
{
    public function __construct(
        private readonly Detector $detector,
        private readonly Dispatcher $events,
    ) {}

    public function handle(Request $request, Closure $next, string ...$targets): Response
    {
        $bot = $this->detector->detect($request)->bot;

        if ($bot === null) {
            return $next($request);
        }

        $targets = $targets !== [] ? $targets : (array) $this->detector->config('bots.block.categories', ['ai-crawler']);
        $allow = (array) $this->detector->config('bots.block.allow', []);

        if ($allow !== [] && $bot->is(...$allow)) {
            return $next($request);
        }

        foreach ($targets as $target) {
            if ($target === '*' || $bot->in($target) || $bot->is($target)) {
                $this->events->dispatch(new BotBlocked($request, $bot));

                abort((int) $this->detector->config('bots.block.status', 403));
            }
        }

        return $next($request);
    }
}
