<?php

declare(strict_types=1);

namespace Atannex\Detector\Http\Middleware;

use Atannex\Detector\Detector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets through only the listed kinds of client, anything Device::is()
 * accepts: ->middleware('device:mobile,tablet'), 'device:ios,android',
 * 'device:!bot'. A "!" excludes. Others get a 404. Alias: 'device'.
 */
final class RestrictDevices
{
    public function __construct(private readonly Detector $detector) {}

    public function handle(Request $request, Closure $next, string ...$names): Response
    {
        $device = $this->detector->detect($request);

        $allow = array_filter($names, fn (string $n) => ! str_starts_with($n, '!'));
        $deny = array_map(fn (string $n) => substr($n, 1), array_filter($names, fn (string $n) => str_starts_with($n, '!')));

        $check = fn (string $name) => match (strtolower($name)) {
            'mobile' => $device->isMobile(),
            'handheld' => $device->isHandheld(),
            'touch' => $device->isTouch(),
            'human' => $device->isHuman(),
            'in-app', 'inapp' => $device->isInApp(),
            'standalone', 'pwa' => $device->isStandalone(),
            default => $device->is($name),
        };

        $allowed = ($allow === [] || array_filter($allow, $check) !== []) && array_filter($deny, $check) === [];

        abort_unless($allowed, 404);

        return $next($request);
    }
}
