<?php

declare(strict_types=1);

namespace Atannex\Detector\Data;

use JsonSerializable;

/**
 * What only the browser itself knows, reported by the @deviceProbe script
 * in a small cookie: touch points (an iPad asks for desktop sites and says
 * "Macintosh"), screen size and density, and whether it runs as an
 * installed web app. Absent on a visitor's first request.
 */
final class Probe implements JsonSerializable
{
    public function __construct(
        public readonly ?int $touchPoints = null,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly ?float $pixelRatio = null,
        public readonly ?bool $standalone = null,
    ) {}

    /** "t=5|w=1024|h=1366|r=2|s=1" as the script writes it. */
    public static function parse(?string $value): ?self
    {
        if ($value === null || $value === '' || strlen($value) > 200) {
            return null;
        }

        $fields = [];

        foreach (preg_split('/[|,;]/', $value) ?: [] as $pair) {
            [$key, $raw] = array_pad(explode('=', $pair, 2), 2, '');

            if (is_numeric($raw)) {
                $fields[trim($key)] = $raw + 0;
            }
        }

        if ($fields === []) {
            return null;
        }

        $int = fn (string $key, int $max) => isset($fields[$key]) && $fields[$key] >= 0 && $fields[$key] <= $max ? (int) $fields[$key] : null;

        return new self(
            $int('t', 64),
            $int('w', 20000),
            $int('h', 20000),
            isset($fields['r']) && $fields['r'] > 0 && $fields['r'] <= 10 ? round((float) $fields['r'], 2) : null,
            isset($fields['s']) ? (bool) $fields['s'] : null,
        );
    }

    public function isTouch(): ?bool
    {
        return $this->touchPoints === null ? null : $this->touchPoints > 0;
    }

    /**
     * @return array{touch_points: ?int, width: ?int, height: ?int, pixel_ratio: ?float, standalone: ?bool}
     */
    public function jsonSerialize(): array
    {
        return [
            'touch_points' => $this->touchPoints,
            'width' => $this->width,
            'height' => $this->height,
            'pixel_ratio' => $this->pixelRatio,
            'standalone' => $this->standalone,
        ];
    }
}
