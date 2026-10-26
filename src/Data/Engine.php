<?php

declare(strict_types=1);

namespace Atannex\Detector\Data;

use Atannex\Detector\Version;
use JsonSerializable;

final class Engine implements JsonSerializable
{
    public readonly Version $version;

    public function __construct(
        public readonly ?string $name = null,
        Version|string|null $version = null,
    ) {
        $this->version = Version::from($version);
    }

    public static function unknown(): self
    {
        return new self;
    }

    public function is(string ...$names): bool
    {
        foreach ($names as $name) {
            if ($this->name !== null && strcasecmp($this->name, $name) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{name: ?string, version: ?string}
     */
    public function jsonSerialize(): array
    {
        return ['name' => $this->name, 'version' => $this->version->jsonSerialize()];
    }
}
