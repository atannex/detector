<?php

declare(strict_types=1);

namespace Atannex\Detector\Http\Middleware;

use Atannex\Detector\Detector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Asks Chromium browsers for the Client Hints the User-Agent no longer
 * carries (Accept-CH), and says the response depends on them (Vary).
 * Alias: 'client-hints'. Hints arrive from the next request on, or on the
 * first one for those listed as critical.
 */
final class RequestClientHints
{
    public function __construct(private readonly Detector $detector) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $hints = $this->detector->clientHints();

        if ($hints === [] || ! $this->isHtml($request, $response)) {
            return $response;
        }

        $merge = function (string $header, array $values) use ($response): void {
            $existing = array_filter(array_map('trim', explode(',', (string) $response->headers->get($header, ''))));
            $response->headers->set($header, implode(', ', array_values(array_unique([...$existing, ...$values]))));
        };

        $merge('Accept-CH', $hints);

        $critical = array_values(array_intersect((array) $this->detector->config('client_hints.critical', []), $hints));

        if ($critical !== []) {
            $merge('Critical-CH', $critical);
        }

        return $response;
    }

    private function isHtml(Request $request, Response $response): bool
    {
        // Accept-CH only takes effect on navigations; skip assets and JSON.
        $type = (string) $response->headers->get('Content-Type', 'text/html');

        return $request->isMethodSafe() && str_contains($type, 'html');
    }
}
