<?php

declare(strict_types=1);

use Atannex\Detector\Detector;
use Atannex\Detector\Device;

if (! function_exists('device')) {
    /**
     * The current request's device, or another request's / User-Agent's.
     */
    function device(\Illuminate\Http\Request|string|null $from = null): Device
    {
        $detector = app(Detector::class);

        return is_string($from) ? $detector->parse($from) : $detector->detect($from);
    }
}
