<?php

declare(strict_types=1);

namespace Atannex\Detector\Contracts;

use Atannex\Detector\ClientHints;
use Atannex\Detector\Data\Probe;
use Atannex\Detector\Device;

/**
 * Turns what a client sends into a Device. The built-in 'native' parser
 * implements it; so does the 'matomo' adapter, and so can your own.
 */
interface Parser
{
    public function parse(string $userAgent, ClientHints $hints, ?Probe $probe = null): Device;
}
