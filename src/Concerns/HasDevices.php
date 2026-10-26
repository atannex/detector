<?php

declare(strict_types=1);

namespace Atannex\Detector\Concerns;

use Atannex\Detector\Models\UserDevice;
use Atannex\Detector\Tracking\DeviceTracker;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;

/**
 * For the authenticatable model: the devices it signed in from, recorded
 * by the track-devices middleware.
 *
 *   $user->devices                       newest first
 *   $user->currentDevice()               this request's record
 *   $user->forgetDevice($device)         drop one (it is "new" again next time)
 *   $user->forgetOtherDevices()          keep only this one
 */
trait HasDevices
{
    /**
     * @return MorphMany<UserDevice, $this>
     */
    public function devices(): MorphMany
    {
        /** @var class-string<UserDevice> $model */
        $model = config('detector.tracking.model', UserDevice::class);

        return $this->morphMany($model, 'authenticatable')->latest('last_seen_at');
    }

    public function currentDevice(?Request $request = null): ?UserDevice
    {
        $id = app(DeviceTracker::class)->idFrom($request ?? request());

        return $id === null ? null : $this->devices()->where('device_id', $id)->first();
    }

    public function hasSeenDevice(?Request $request = null): bool
    {
        return $this->currentDevice($request) !== null;
    }

    public function forgetDevice(UserDevice|int $device): bool
    {
        return (bool) $this->devices()->whereKey($device instanceof UserDevice ? $device->getKey() : $device)->delete();
    }

    public function forgetOtherDevices(?Request $request = null): int
    {
        $id = app(DeviceTracker::class)->idFrom($request ?? request());

        return $this->devices()->when($id !== null, fn ($query) => $query->where('device_id', '!=', $id))->delete();
    }
}
