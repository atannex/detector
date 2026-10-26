<?php

declare(strict_types=1);

namespace Atannex\Detector\Tracking;

use Atannex\Detector\Detector;
use Atannex\Detector\Events\NewDeviceDetected;
use Atannex\Detector\Models\UserDevice;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records which devices a user signs in from. A device is a browser
 * profile holding a random id in a long-lived, encrypted, HTTP-only cookie:
 * no fingerprinting, and clearing cookies makes it a new device.
 */
final class DeviceTracker
{
    /** @var array<string, mixed> */
    private readonly array $config;

    public function __construct(
        private readonly Detector $detector,
        private readonly Dispatcher $events,
    ) {
        $this->config = (array) $detector->config('tracking', []);
    }

    /**
     * Record the request's device for the user and make sure the response
     * carries the device cookie. Bots are not tracked.
     */
    public function track(Request $request, Model $user, ?Response $response = null): ?UserDevice
    {
        $device = $this->detector->detect($request);

        if ($device->isBot()) {
            return null;
        }

        $id = $this->idFrom($request);

        if ($id === null) {
            $id = Str::random(40);
            $request->cookies->set($this->cookieName(), $id);
        }

        $response?->headers->setCookie($this->cookie($id));

        /** @var class-string<UserDevice> $model */
        $model = $this->config['model'] ?? UserDevice::class;
        $now = now();

        $attributes = [
            'type' => $device->type,
            'brand' => $device->brand,
            'model' => $device->model,
            'os_name' => $device->os->name,
            'os_version' => $device->os->version->jsonSerialize(),
            'browser_name' => $device->browser->name,
            'browser_version' => $device->browser->version->jsonSerialize(),
            'app' => $device->app,
            'ip_address' => ($this->config['store_ip'] ?? true) ? $request->ip() : null,
            'user_agent' => $device->userAgent === '' ? null : $device->userAgent,
        ];

        $query = $model::query()
            ->where('authenticatable_type', $user->getMorphClass())
            ->where('authenticatable_id', $user->getKey());

        /** @var UserDevice|null $record */
        $record = (clone $query)->where('device_id', $id)->first();

        if ($record === null) {
            $isFirst = ! (clone $query)->exists();

            $record = new $model;
            $record->forceFill([
                ...$attributes,
                'authenticatable_type' => $user->getMorphClass(),
                'authenticatable_id' => $user->getKey(),
                'device_id' => $id,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ])->save();

            if (! $isFirst || ($this->config['notify_first'] ?? false)) {
                $this->events->dispatch(new NewDeviceDetected($user, $record, $device));
            }

            return $record;
        }

        $record->forceFill($attributes);

        $due = $record->last_seen_at === null || $record->last_seen_at->diffInSeconds($now, true) >= (int) ($this->config['touch_every'] ?? 300);

        // A phone changes IP all day: that alone is not worth a write.
        if ($due || $record->isDirty(array_diff(array_keys($attributes), ['ip_address']))) {
            $record->forceFill(['last_seen_at' => $now])->save();
        }

        return $record;
    }

    public function idFrom(Request $request): ?string
    {
        $id = $request->cookies->get($this->cookieName());

        return is_string($id) && preg_match('/^[A-Za-z0-9]{20,64}$/', $id) ? $id : null;
    }

    public function cookieName(): string
    {
        return (string) ($this->config['cookie'] ?? 'device_id');
    }

    private function cookie(string $id): Cookie
    {
        return Cookie::create(
            $this->cookieName(),
            $id,
            now()->addMinutes((int) ($this->config['cookie_minutes'] ?? 2628000)),
            (string) config('session.path', '/'),
            config('session.domain'),
            config('session.secure'),
            true,
            false,
            (string) (config('session.same_site') ?? 'lax'),
        );
    }
}
