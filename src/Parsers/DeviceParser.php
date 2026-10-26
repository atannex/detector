<?php

declare(strict_types=1);

namespace Atannex\Detector\Parsers;

use Atannex\Detector\ClientHints;
use Atannex\Detector\Data\Os;
use Atannex\Detector\Enums\DeviceType;

/** @internal */
final class DeviceParser
{
    /** First match wins: [pattern, type]. */
    private const TYPES = [
        ['OculusBrowser|\bQuest\b|PicoBrowser|Pico Neo|\bWolvic\b|visionOS|Vision Pro|\bHoloLens\b|\bMagic ?Leap\b', DeviceType::Xr],
        ['QtCarBrowser|\bTesla\b|Android Automotive|\bAAOS\b|CarPlay|\bPolestar\b', DeviceType::Car],
        ['PlayStation|PLAYSTATION|\bXbox\b|Nintendo|\bOUYA\b', DeviceType::Console],
        ['Smart-?TV|SmartHub|HbbTV|NetCast|BRAVIA|Android ?TV|Google ?TV|\bAFT[A-Z0-9]+\b|\bCrKey\b|\bRoku\b|AppleTV|Apple ?TV|\btvOS\b|Web0S|webOS.*\bTV\b|Tizen.*\bTV\b|\bViera\b|PhilipsTV|NETTV|VIDAA|\bDTV\b|\bTV Safari\b|Opera TV|Large Screen|SHIELD Android TV|\bMiTV\b|MiBOX|\bSTB\b|Chromecast|Fire ?TV|\bTV\)|; TV;', DeviceType::Tv],
        ['Echo Show|AlexaMediaPlayer|Nest Hub|\bFuchsia\b|Portal\b.*Facebook', DeviceType::SmartDisplay],
        ['Kindle/\d(?!.*Silk)|\bKobo\b|PocketBook|\bNook\b|reMarkable|\bBOOX\b|\bTolino\b|\bE-?Ink\b', DeviceType::EReader],
        ['\bwatchOS\b|Watch OS|Watch\d+,\d+|Apple ?Watch|Wear ?OS|SM-R\d{3}|\bGarmin\b|\bFitbit\b|; Watch\b', DeviceType::Wearable],
        ['iPod', DeviceType::MediaPlayer],
        ['iPad|Tablet|PlayBook|RIM Tablet|Kindle Fire|\bKF[A-Z]{2,4}\b|Silk/(?!.*Mobile)|Nexus (?:7|9|10)\b|\bSM-[TPX]\d|GT-P\d|\bLenovo TB|\bTB-\w|\bYT-|MediaPad|MatePad|Pixel Tablet|Redmi Pad|Mi Pad|Xiaomi Pad|Galaxy Tab|\bTab\b|\bTAB\b|\bP[0-9]{2}T?\b.*Tablet', DeviceType::Tablet],
        ['KAIOS|Series ?40|J2ME|\bMIDP\b|\bMAUI\b|Nokia\d{3,4}[^0-9]|UCWEB.*(?:Java|MIDP)|Opera Mini.*(?:J2ME|Java)', DeviceType::FeaturePhone],
        ['iPhone|Windows Phone|IEMobile|BB10|BlackBerry|Mobile Safari|Opera Mini|Opera Mobi|\bMobi\b|\bMobile\b|Symbian|\bwebOS\b|hpwOS|\bFennec\b|Sailfish|\bHarmonyOS\b.*Phone|SymbOS', DeviceType::Smartphone],
        ['Android', DeviceType::Tablet],
        ['Windows NT|Macintosh|Mac_PowerPC|X11|CrOS|Linux|FreeBSD|OpenBSD|NetBSD|SunOS|Win(?:98|95|dows)|Haiku', DeviceType::Desktop],
    ];

    /** Sec-CH-UA-Form-Factors, most specific first. */
    private const FORM_FACTORS = [
        'xr' => DeviceType::Xr,
        'automotive' => DeviceType::Car,
        'watch' => DeviceType::Wearable,
        'eink' => DeviceType::EReader,
        'tablet' => DeviceType::Tablet,
        'mobile' => DeviceType::Smartphone,
        'desktop' => DeviceType::Desktop,
    ];

    /** @var list<array{0: string, 1: DeviceType}>|null */
    private static ?array $compiled = null;

    /**
     * @return array{0: DeviceType, 1: ?string, 2: ?string} type, brand, model
     */
    public function parse(string $userAgent, Os $os): array
    {
        $type = DeviceType::Unknown;

        foreach (self::types() as [$pattern, $candidate]) {
            if (preg_match($pattern, $userAgent)) {
                $type = $candidate;
                break;
            }
        }

        // An Android TV box or a Fire TV stick does not say "Mobile" either.
        if ($type === DeviceType::Tablet && $os->family === 'android' && preg_match('~\bTV\b|\bAFT~', $userAgent)) {
            $type = DeviceType::Tv;
        }

        [$brand, $model] = $this->identify($userAgent, $os);

        return [$type, $brand, $model];
    }

    /**
     * @return array{0: ?string, 1: ?string} brand, model
     */
    public function identify(string $userAgent, Os $os): array
    {
        if ($apple = $this->apple($userAgent)) {
            return $apple;
        }

        $model = $this->model($userAgent);
        $brand = $model !== null ? $this->brand($model) : null;
        $brand ??= $this->brand($userAgent, isModel: false);

        return [$brand, $model !== null && $brand !== null ? self::withoutBrand($model, $brand) : $model];
    }

    /**
     * Sec-CH-UA-Model and Sec-CH-UA-Form-Factors over the User-Agent.
     *
     * @return array{0: DeviceType, 1: ?string, 2: ?string}
     */
    public function fromHints(ClientHints $hints, DeviceType $type, ?string $brand, ?string $model): array
    {
        foreach (self::FORM_FACTORS as $factor => $candidate) {
            foreach ($hints->formFactors as $given) {
                if (strtolower(str_replace(['-', ' '], '', $given)) === $factor) {
                    $type = $candidate;
                    break 2;
                }
            }
        }

        if ($hints->formFactors === [] && $hints->mobile === true && in_array($type, [DeviceType::Unknown, DeviceType::Desktop, DeviceType::Tablet], true)) {
            $type = DeviceType::Smartphone;
        }

        if ($hints->model !== null && $hints->model !== '') {
            $hinted = $this->brand($hints->model);
            $brand = $hinted ?? $brand;
            $model = $brand !== null ? self::withoutBrand($hints->model, $brand) : $hints->model;
        }

        return [$type, $brand, $model];
    }

    /**
     * The brand a model code or name belongs to; with $isModel false the
     * subject is a whole User-Agent and only the unanchored rules apply.
     */
    public function brand(string $subject, bool $isModel = true): ?string
    {
        foreach (Rules::get('devices') as [$pattern, $brand]) {
            if (! $isModel && str_starts_with($pattern, '~^')) {
                continue;
            }

            if (preg_match($pattern, $subject)) {
                return $brand;
            }
        }

        return null;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function apple(string $userAgent): ?array
    {
        return match (true) {
            (bool) preg_match('~\biPod~', $userAgent) => ['Apple', 'iPod touch'],
            (bool) preg_match('~\biPad~', $userAgent) => ['Apple', 'iPad'],
            (bool) preg_match('~\biPhone~', $userAgent) => ['Apple', 'iPhone'],
            (bool) preg_match('~AppleTV|Apple ?TV~i', $userAgent) => ['Apple', 'Apple TV'],
            (bool) preg_match('~Watch\d+,\d+|watchOS~', $userAgent) => ['Apple', 'Apple Watch'],
            (bool) preg_match('~visionOS|Vision Pro~', $userAgent) => ['Apple', 'Apple Vision Pro'],
            (bool) preg_match('~Macintosh|Mac_PowerPC~', $userAgent) => ['Apple', 'Mac'],
            default => null,
        };
    }

    private function model(string $userAgent): ?string
    {
        $candidates = [
            // Windows Phone 10.0; Android 6.0.1; Microsoft; Lumia 950)
            '~Windows Phone.*?;\s*(?:Microsoft(?: Mobile)?|NOKIA|HTC|SAMSUNG|LG|Acer|Alcatel|HUAWEI)\s*;\s*([^;)]+)~i',
            // Android 14; SM-S918B Build/UP1A…) / Android 10; K) / Linux; U; Android 4.4.2; en-us; GT-I9505 Build/… / moto g(60))
            '~Android[ /-]?[\d.]*;(?:\s*[UIN];)?(?:\s*[a-z]{2,3}(?:[-_][a-z]{2,4})?;)?\s*((?:[^;()]|\([^;()]*\))+?)(?:\s+Build/[^;)]*)?\s*(?:[;)]|$)~i',
            // KaiOS: (Mobile; Nokia_8110_4G; rv:48.0)
            '~\(Mobile; (?!rv:)([^;)]+);(?: \w+;)? rv:~i',
            // HarmonyOS: (Phone; ALN-AL00; HarmonyOS 4.0)
            '~\((?:Phone|Tablet); ([^;)]+); HarmonyOS~i',
            // BlackBerry 9900 / BB10; Kbd)
            '~BlackBerry ?(\d{4,})~i',
            // Kindle Fire, Fire TV and Echo models
            '~\b(KF[A-Z]{2,4}|AFT[A-Z0-9]+|AEO[A-Z]{2})\b~',
        ];

        foreach ($candidates as $pattern) {
            if (preg_match($pattern, $userAgent, $m)) {
                $model = trim(str_replace('_', ' ', $m[1]));

                if (self::isPlaceholder($model)) {
                    continue;
                }

                return $model;
            }
        }

        return null;
    }

    /** Chrome's reduced User-Agent says "K"; others leave locales, "wv" or "Linux" there. */
    private static function isPlaceholder(string $model): bool
    {
        return $model === '' || strlen($model) > 64 || str_contains($model, '/')
            || (bool) preg_match('~^(?:K|wv|Linux|Mobile|Tablet|U|en|Android|Build/.*|[a-z]{2}[-_][a-z]{2}|rv:.*|x86_64|armv\w+|aarch64|Unknown)$~i', $model);
    }

    private static function withoutBrand(string $model, string $brand): string
    {
        $prefixes = [$brand, ...match ($brand) {
            'Xiaomi' => ['XiaoMi', 'MI'],
            'Samsung' => ['SAMSUNG'],
            'Huawei' => ['HUAWEI'],
            'Tecno' => ['TECNO'],
            'Sony' => ['Sony Ericsson'],
            default => [],
        }];

        foreach ($prefixes as $prefix) {
            if (preg_match('~^'.preg_quote($prefix, '~').'[\s_-]+(.+\d.*)$~i', $model, $m)) {
                return trim($m[1]);
            }
        }

        return $model;
    }

    /**
     * @return list<array{0: string, 1: DeviceType}>
     */
    private static function types(): array
    {
        return self::$compiled ??= array_map(fn (array $rule) => [Rules::compile($rule[0]), $rule[1]], self::TYPES);
    }
}
