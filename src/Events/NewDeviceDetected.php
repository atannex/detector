<?php

declare(strict_types=1);

namespace Atannex\Detector\Events;

use Atannex\Detector\Device;
use Atannex\Detector\Models\UserDevice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A signed-in user was seen on a device they have not used before: the
 * place for a "new sign-in on Chrome on Windows" e-mail.
 */
final class NewDeviceDetected
{
    use Dispatchable;

    public function __construct(
        public readonly Model $user,
        public readonly UserDevice $record,
        public readonly Device $device,
    ) {}
}
