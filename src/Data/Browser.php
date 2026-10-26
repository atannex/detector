<?php

declare(strict_types=1);

namespace Atannex\Detector\Data;

use Atannex\Detector\Version;
use JsonSerializable;

final class Browser implements JsonSerializable
{
    public readonly Version $version;

    /**
     * @param  string|null  $family  the browser it is built on: Chrome, Firefox, Safari, Internet Explorer…
     */
    public function __construct(
        public readonly ?string $name = null,
        Version|string|null $version = null,
        public readonly ?string $family = null,
    ) {
        $this->version = Version::from($version);
    }

    public static function unknown(): self
    {
        return new self;
    }

    public function isKnown(): bool
    {
        return $this->name !== null;
    }

    /** By exact name: is('Chrome') is false for Edge; use isBasedOn('Chrome') for that. */
    public function is(string ...$names): bool
    {
        foreach ($names as $name) {
            if ($this->name !== null && strcasecmp($this->name, $name) === 0) {
                return true;
            }
        }

        return false;
    }

    /** By name or family: isBasedOn('Chrome') holds for Chrome, Edge, Opera, Samsung Internet… */
    public function isBasedOn(string ...$families): bool
    {
        foreach ($families as $family) {
            if (($this->family !== null && strcasecmp($this->family, $family) === 0) || $this->is($family)) {
                return true;
            }
        }

        return false;
    }

    public function label(): ?string
    {
        return $this->name === null ? null : trim($this->name.' '.$this->version->short());
    }

    /**
     * @return array{name: ?string, version: ?string, family: ?string}
     */
    public function jsonSerialize(): array
    {
        return ['name' => $this->name, 'version' => $this->version->jsonSerialize(), 'family' => $this->family];
    }
}
