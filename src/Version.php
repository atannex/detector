<?php

declare(strict_types=1);

namespace Atannex\Detector;

use JsonSerializable;
use Stringable;

/**
 * A dotted version ("17.4.1", "10_15_7" and "120.0.6099.129" alike) that can
 * be compared: $device->os->version->isAtLeast('17').
 */
final class Version implements JsonSerializable, Stringable
{
    private function __construct(public readonly string $value) {}

    public static function from(Version|string|int|float|null $version): self
    {
        if ($version instanceof self) {
            return $version;
        }

        $value = str_replace('_', '.', trim((string) $version));

        // Numeric ("10.15.7") or a named release some systems use ("XP", "Vista").
        $value = match (true) {
            (bool) preg_match('/^\d+(?:\.\d+)*/', $value, $m) => $m[0],
            (bool) preg_match('/^[A-Za-z][A-Za-z0-9]{0,15}$/', $value) => $value,
            default => '',
        };

        return new self($value);
    }

    public static function none(): self
    {
        return new self('');
    }

    public function isKnown(): bool
    {
        return $this->value !== '';
    }

    public function major(): ?int
    {
        return $this->part(0);
    }

    public function minor(): ?int
    {
        return $this->part(1);
    }

    public function patch(): ?int
    {
        return $this->part(2);
    }

    /** "120.0.0.0" → "120", "17.4.0" → "17.4": what people write. */
    public function short(): string
    {
        return preg_replace('/(?:\.0)+$/', '', $this->value) ?? $this->value;
    }

    public function compare(Version|string|int|float $other): int
    {
        $a = $this->parts();
        $b = self::from($other)->parts();
        $length = max(count($a), count($b));

        for ($i = 0; $i < $length; $i++) {
            $cmp = ($a[$i] ?? 0) <=> ($b[$i] ?? 0);

            if ($cmp !== 0) {
                return $cmp;
            }
        }

        return 0;
    }

    /** Unknown versions are never at least anything. */
    public function isAtLeast(Version|string|int|float $other): bool
    {
        return $this->isKnown() && $this->compare($other) >= 0;
    }

    public function isBelow(Version|string|int|float $other): bool
    {
        return $this->isKnown() && $this->compare($other) < 0;
    }

    public function is(Version|string|int|float $other): bool
    {
        $other = self::from($other);

        // "17" matches 17.4.1: compare only as many parts as were given.
        return $this->isKnown() && $other->isKnown()
            && array_slice($this->parts(), 0, count($other->parts())) === $other->parts();
    }

    /**
     * @return list<int>
     */
    public function parts(): array
    {
        return $this->value === '' || ! ctype_digit($this->value[0]) ? [] : array_map('intval', explode('.', $this->value));
    }

    private function part(int $index): ?int
    {
        return $this->parts()[$index] ?? null;
    }

    public function jsonSerialize(): ?string
    {
        return $this->value === '' ? null : $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
