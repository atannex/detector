<?php

declare(strict_types=1);

namespace Atannex\Detector\Models;

use Atannex\Detector\Enums\DeviceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * A device a user has signed in from.
 *
 * @property string $device_id
 * @property string|null $name
 * @property DeviceType $type
 * @property string|null $brand
 * @property string|null $model
 * @property string|null $os_name
 * @property string|null $os_version
 * @property string|null $browser_name
 * @property string|null $browser_version
 * @property string|null $app
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $trusted_at
 * @property Carbon|null $first_seen_at
 * @property Carbon|null $last_seen_at
 */
class UserDevice extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['device_id'];

    protected function casts(): array
    {
        return [
            'type' => DeviceType::class,
            'trusted_at' => 'datetime',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return (string) config('detector.tracking.table', parent::getTable());
    }

    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    /** The device the request comes from. */
    public function isCurrent(?Request $request = null): bool
    {
        $request ??= request();

        return hash_equals($this->device_id, (string) $request->cookies->get((string) config('detector.tracking.cookie', 'device_id')));
    }

    public function isTrusted(): bool
    {
        return $this->trusted_at !== null;
    }

    public function trust(): static
    {
        $this->forceFill(['trusted_at' => now()])->save();

        return $this;
    }

    public function untrust(): static
    {
        $this->forceFill(['trusted_at' => null])->save();

        return $this;
    }

    /** The name the user gave it, or "Chrome on Windows 11". */
    public function label(): string
    {
        if ($this->name !== null && $this->name !== '') {
            return $this->name;
        }

        $client = $this->app ?? trim(($this->browser_name ?? '').' ');
        $os = trim(($this->os_name ?? '').' '.($this->os_version ?? ''));

        return match (true) {
            $client !== '' && $os !== '' => "{$client} on {$os}",
            $client !== '' => $client,
            $os !== '' => $os,
            default => $this->type->label(),
        };
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeStale(Builder $query, int $days): void
    {
        $query->where('last_seen_at', '<', now()->subDays($days));
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeTrusted(Builder $query): void
    {
        $query->whereNotNull('trusted_at');
    }
}
