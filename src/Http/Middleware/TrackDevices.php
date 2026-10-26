<?php

declare(strict_types=1);

namespace Atannex\Detector\Http\Middleware;

use Atannex\Detector\Tracking\DeviceTracker;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records the signed-in user's device after the request, so a sign-in
 * counts on the request that signs in. Alias: 'track-devices'; pass a
 * guard to watch another one: 'track-devices:admin'.
 */
final class TrackDevices
{
    public function __construct(private readonly DeviceTracker $tracker) {}

    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $user = $request->user($guard);

        if ($user instanceof Model) {
            $this->tracker->track($request, $user, $response);
        }

        return $response;
    }
}
