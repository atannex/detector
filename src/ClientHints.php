<?php

declare(strict_types=1);

namespace Atannex\Detector;

use Illuminate\Http\Request;
use JsonSerializable;

/**
 * User-Agent Client Hints (Sec-CH-UA-*). Chromium browsers freeze their
 * User-Agent: Windows 11 still says "Windows NT 10.0", Android hides the
 * model behind "K" and versions read "120.0.0.0". The hints carry the real
 * values once the site asks for them (the client-hints middleware does).
 */
final class ClientHints implements JsonSerializable
{
    /** Low-entropy hints, sent on every request by Chromium. */
    public const LOW_ENTROPY = ['Sec-CH-UA', 'Sec-CH-UA-Mobile', 'Sec-CH-UA-Platform'];

    /** High-entropy hints, sent after Accept-CH asks for them. */
    public const HIGH_ENTROPY = [
        'Sec-CH-UA-Full-Version-List',
        'Sec-CH-UA-Platform-Version',
        'Sec-CH-UA-Model',
        'Sec-CH-UA-Arch',
        'Sec-CH-UA-Bitness',
        'Sec-CH-UA-Form-Factors',
        'Sec-CH-UA-WoW64',
    ];

    /**
     * @param  array<string, string>  $brands  brand => major version, GREASE removed
     * @param  array<string, string>  $fullVersions  brand => full version
     * @param  list<string>  $formFactors
     */
    public function __construct(
        public readonly array $brands = [],
        public readonly array $fullVersions = [],
        public readonly ?bool $mobile = null,
        public readonly ?string $platform = null,
        public readonly ?string $platformVersion = null,
        public readonly ?string $model = null,
        public readonly ?string $architecture = null,
        public readonly ?string $bitness = null,
        public readonly array $formFactors = [],
        public readonly ?bool $wow64 = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return self::fromHeaders($request->headers->all());
    }

    /**
     * @param  array<string, string|list<string|null>|null>  $headers  any case; also HTTP_SEC_CH_UA style ($_SERVER)
     */
    public static function fromHeaders(array $headers): self
    {
        $normalised = [];

        foreach ($headers as $name => $value) {
            $key = strtolower(str_replace('_', '-', (string) $name));
            $key = str_starts_with($key, 'http-') ? substr($key, 5) : $key;
            $normalised[$key] = is_array($value) ? implode(', ', array_filter($value, 'is_string')) : (string) $value;
        }

        $get = fn (string $name) => isset($normalised[strtolower($name)]) && trim($normalised[strtolower($name)]) !== '' ? trim($normalised[strtolower($name)]) : null;

        return new self(
            self::brandList($get('Sec-CH-UA')),
            self::brandList($get('Sec-CH-UA-Full-Version-List')),
            self::boolean($get('Sec-CH-UA-Mobile')),
            self::string($get('Sec-CH-UA-Platform')),
            self::string($get('Sec-CH-UA-Platform-Version')),
            self::string($get('Sec-CH-UA-Model')),
            self::string($get('Sec-CH-UA-Arch')),
            self::string($get('Sec-CH-UA-Bitness')),
            self::stringList($get('Sec-CH-UA-Form-Factors')),
            self::boolean($get('Sec-CH-UA-WoW64')),
        );
    }

    public function isEmpty(): bool
    {
        return $this->brands === [] && $this->fullVersions === [] && $this->mobile === null && $this->platform === null
            && $this->model === null && $this->formFactors === [];
    }

    /**
     * The brand that names the browser: "Microsoft Edge" over "Chromium".
     *
     * @return array{0: string, 1: string}|null [brand, version]
     */
    public function brand(): ?array
    {
        $list = $this->fullVersions !== [] ? $this->fullVersions : $this->brands;

        if ($list === []) {
            return null;
        }

        foreach ($list as $brand => $version) {
            if (strcasecmp($brand, 'Chromium') !== 0) {
                return [$brand, $version];
            }
        }

        return [array_key_first($list), $list[array_key_first($list)]];
    }

    /** A stable key of what parsing depends on, for caches. */
    public function key(): string
    {
        return $this->isEmpty() ? '' : md5(serialize($this->jsonSerialize()));
    }

    /**
     * @return array<string, string>
     */
    public function toHeaders(): array
    {
        $list = fn (array $brands) => implode(', ', array_map(fn ($b, $v) => '"'.addcslashes((string) $b, '"\\').'";v="'.addcslashes((string) $v, '"\\').'"', array_keys($brands), $brands));
        $quote = fn (?string $v) => $v === null ? null : '"'.addcslashes($v, '"\\').'"';

        return array_filter([
            'Sec-CH-UA' => $this->brands === [] ? null : $list($this->brands),
            'Sec-CH-UA-Full-Version-List' => $this->fullVersions === [] ? null : $list($this->fullVersions),
            'Sec-CH-UA-Mobile' => $this->mobile === null ? null : ($this->mobile ? '?1' : '?0'),
            'Sec-CH-UA-Platform' => $quote($this->platform),
            'Sec-CH-UA-Platform-Version' => $quote($this->platformVersion),
            'Sec-CH-UA-Model' => $this->model === null ? null : $quote($this->model),
            'Sec-CH-UA-Arch' => $quote($this->architecture),
            'Sec-CH-UA-Bitness' => $quote($this->bitness),
            'Sec-CH-UA-Form-Factors' => $this->formFactors === [] ? null : implode(', ', array_map($quote, $this->formFactors)),
            'Sec-CH-UA-WoW64' => $this->wow64 === null ? null : ($this->wow64 ? '?1' : '?0'),
        ], fn ($v) => $v !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'brands' => $this->brands,
            'full_versions' => $this->fullVersions,
            'mobile' => $this->mobile,
            'platform' => $this->platform,
            'platform_version' => $this->platformVersion,
            'model' => $this->model,
            'architecture' => $this->architecture,
            'bitness' => $this->bitness,
            'form_factors' => $this->formFactors,
            'wow64' => $this->wow64,
        ];
    }

    /**
     * RFC 8941 list of strings with a v parameter, GREASE brands dropped:
     * "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120".
     *
     * @return array<string, string>
     */
    private static function brandList(?string $value): array
    {
        if ($value === null || ! preg_match_all('/"((?:[^"\\\\]|\\\\.)*)"\s*;\s*v\s*=\s*"((?:[^"\\\\]|\\\\.)*)"/', $value, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $brands = [];

        foreach ($matches as [, $brand, $version]) {
            $brand = stripslashes($brand);

            // GREASE: "Not A(Brand", "Not)A;Brand", " Not;A Brand"…
            if (preg_match('/^\W*Not\W*A\W*Brand\W*$/i', $brand) || trim($brand) === '') {
                continue;
            }

            $brands[$brand] = stripslashes($version);
        }

        return $brands;
    }

    private static function string(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        $value = preg_match('/^"((?:[^"\\\\]|\\\\.)*)"$/', $value, $m) ? stripslashes($m[1]) : $value;

        return trim($value) === '' ? null : trim($value);
    }

    /**
     * @return list<string>
     */
    private static function stringList(?string $value): array
    {
        if ($value === null || ! preg_match_all('/"((?:[^"\\\\]|\\\\.)*)"/', $value, $matches)) {
            return [];
        }

        return array_values(array_filter(array_map('stripslashes', $matches[1]), fn (string $v) => $v !== ''));
    }

    private static function boolean(?string $value): ?bool
    {
        return match ($value) {
            '?1' => true,
            '?0' => false,
            default => null,
        };
    }
}
