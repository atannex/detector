<?php

declare(strict_types=1);

namespace Atannex\Detector\Enums;

/**
 * The kind of hardware a request comes from. Bots keep the type they
 * present (Googlebot Smartphone is a smartphone); Device::$bot says it is one.
 */
enum DeviceType: string
{
    case Desktop = 'desktop';
    case Smartphone = 'smartphone';
    case Tablet = 'tablet';
    case FeaturePhone = 'feature-phone';
    case Wearable = 'wearable';
    case Tv = 'tv';
    case Console = 'console';
    case Xr = 'xr';
    case Car = 'car';
    case EReader = 'e-reader';
    case SmartDisplay = 'smart-display';
    case MediaPlayer = 'media-player';
    case Unknown = 'unknown';

    public static function fromName(string $name): ?self
    {
        $name = strtolower(trim($name));

        return self::tryFrom($name) ?? match ($name) {
            'mobile', 'phone', 'smartphone', 'phablet' => self::Smartphone,
            'feature phone', 'featurephone' => self::FeaturePhone,
            'watch' => self::Wearable,
            'television', 'smart tv', 'smarttv', 'tv' => self::Tv,
            'vr', 'ar', 'headset' => self::Xr,
            'automotive', 'car browser' => self::Car,
            'ereader', 'eink', 'e-ink' => self::EReader,
            'smart display', 'smart speaker' => self::SmartDisplay,
            'portable media player', 'media player' => self::MediaPlayer,
            default => null,
        };
    }

    /** Phones in the hand: what most "is mobile" checks mean. */
    public function isMobile(): bool
    {
        return in_array($this, [self::Smartphone, self::FeaturePhone], true);
    }

    /** Anything carried: phones, tablets, watches, e-readers, media players. */
    public function isHandheld(): bool
    {
        return in_array($this, [self::Smartphone, self::FeaturePhone, self::Tablet, self::Wearable, self::EReader, self::MediaPlayer], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::FeaturePhone => 'Feature phone',
            self::Tv => 'TV',
            self::Xr => 'XR headset',
            self::EReader => 'E-reader',
            self::SmartDisplay => 'Smart display',
            self::MediaPlayer => 'Media player',
            default => ucfirst($this->value),
        };
    }
}
