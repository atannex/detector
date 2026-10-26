<?php

declare(strict_types=1);

namespace Atannex\Detector\Data;

use Atannex\Detector\Version;
use JsonSerializable;

final class Os implements JsonSerializable
{
    public readonly Version $version;

    /**
     * @param  string|null  $family  windows, apple, android, linux, chromeos, bsd, console, mobile, tv or other
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

    public function is(string ...$names): bool
    {
        foreach ($names as $name) {
            if ($this->name !== null && (strcasecmp($this->name, $name) === 0 || ($this->family !== null && strcasecmp($this->family, $name) === 0))) {
                return true;
            }
        }

        return false;
    }

    public function with(?string $name = null, Version|string|null $version = null, ?string $family = null): self
    {
        return new self($name ?? $this->name, $version ?? $this->version, $family ?? $this->family);
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
