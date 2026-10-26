<?php

declare(strict_types=1);

namespace Atannex\Detector\Parsers;

use Atannex\Detector\ClientHints;
use Atannex\Detector\Data\Os;

/** @internal */
final class OsParser
{
    /** First match wins: [pattern, name, family]. */
    private const RULES = [
        ['HarmonyOS[ /]?(?<v>[\d.]+)?|OpenHarmony[ /-]?(?<v2>[\d.]+)?', 'HarmonyOS', 'android'],
        ['Windows Phone(?: OS)?[ /]?(?<v>[\d.]+)?|WPDesktop', 'Windows Phone', 'windows'],
        ['Windows (?:Mobile|CE)', 'Windows Mobile', 'windows'],
        ['Xbox', 'Xbox', 'console'],
        ['Windows NT (?<v>[\d.]+)', 'Windows', 'windows'],
        ['Windows (?<v>98|95|ME|2000|XP)|Win(?<v2>98|95|NT)\b', 'Windows', 'windows'],
        ['KAIOS/(?<v>[\d.]+)', 'KaiOS', 'mobile'],
        ['CrOS \S+ (?<v>[\d.]+)', 'ChromeOS', 'chromeos'],
        ['Tizen[ /]?(?<v>[\d.]+)?', 'Tizen', 'other'],
        ['Web0S|webOS(?:\.TV)?(?:[ /-](?<v>[\d.]+))?|hpwOS(?:/(?<v2>[\d.]+))?', 'webOS', 'other'],
        ['Sailfish', 'Sailfish OS', 'linux'],
        ['PlayStation (?<v>4|5|Vita|Portable|3)|PLAYSTATION (?<v2>\d)', 'PlayStation', 'console'],
        ['Nintendo', 'Nintendo', 'console'],
        ['Roku(?:OS)?/(?:DVP-)?(?<v>[\d.]+)?|\bRoku\b', 'Roku OS', 'tv'],
        ['Wear ?OS', 'Wear OS', 'android'],
        ['Fire ?OS[ /]?(?<v>[\d.]+)?', 'Fire OS', 'android'],
        ['(?<!like )Android(?!TV)[ /-]?(?<v>[\d.]+)?|\bAdr (?<v2>[\d.]+)', 'Android', 'android'],
        ['watchOS[ /]?(?<v>[\d.]+)?|Watch OS[ /]?(?<v2>[\d.]+)?|Watch\d+,\d+', 'watchOS', 'apple'],
        ['AppleTV|Apple ?TV|tvOS[ /]?(?<v>[\d.]+)?', 'tvOS', 'apple'],
        ['visionOS[ /]?(?<v>[\d.]+)?', 'visionOS', 'apple'],
        ['(?:iPhone|iPad|iPod).*? OS (?<v>[\d_]+)|\biOS[ /](?<v2>[\d._]+)|iPhone|iPad|iPod', 'iOS', 'apple'],
        ['Mac OS X[ /_]?(?<v>[\d_.]+)|macOS[ /](?<v2>[\d.]+)', 'macOS', 'apple'],
        ['Macintosh|Mac_PowerPC|Mac OS X|macOS', 'macOS', 'apple'],
        ['BlackBerry|BB10|RIM Tablet OS ?(?<v>[\d.]+)?', 'BlackBerry OS', 'mobile'],
        ['Symbian(?:OS)?/?(?<v>[\d.]+)?|SymbOS|Series ?60', 'Symbian', 'mobile'],
        ['Series ?40', 'Series 40', 'mobile'],
        ['Fuchsia', 'Fuchsia', 'other'],
        ['FreeBSD', 'FreeBSD', 'bsd'],
        ['OpenBSD', 'OpenBSD', 'bsd'],
        ['NetBSD', 'NetBSD', 'bsd'],
        ['DragonFly', 'DragonFly BSD', 'bsd'],
        ['SunOS|Solaris', 'Solaris', 'other'],
        ['Haiku', 'Haiku', 'other'],
        ['Ubuntu(?:[ /](?<v>[\d.]+))?|Kubuntu|Xubuntu|Lubuntu', 'Ubuntu', 'linux'],
        ['Linux Mint|\bMint/(?<v>[\d.]+)', 'Linux Mint', 'linux'],
        ['Fedora(?:/[\d.]+-(?<v>\d+))?', 'Fedora', 'linux'],
        ['Debian', 'Debian', 'linux'],
        ['Arch ?Linux', 'Arch Linux', 'linux'],
        ['Manjaro', 'Manjaro', 'linux'],
        ['CentOS', 'CentOS', 'linux'],
        ['Red Hat|RHEL', 'Red Hat', 'linux'],
        ['openSUSE|SUSE', 'openSUSE', 'linux'],
        ['Gentoo', 'Gentoo', 'linux'],
        ['Linux|X11', 'Linux', 'linux'],
    ];

    private const WINDOWS = [
        '10.0' => '10', '6.4' => '10', '6.3' => '8.1', '6.2' => '8', '6.1' => '7', '6.0' => 'Vista',
        '5.2' => 'XP', '5.1' => 'XP', '5.01' => '2000', '5.0' => '2000', '4.0' => 'NT',
        '98' => '98', '95' => '95', 'ME' => 'ME', '2000' => '2000', 'XP' => 'XP', 'NT' => 'NT',
    ];

    /** @var list<array{0: string, 1: string, 2: string}>|null */
    private static ?array $compiled = null;

    public function parse(string $userAgent): Os
    {
        foreach (self::rules() as [$pattern, $name, $family]) {
            if (preg_match($pattern, $userAgent, $m)) {
                $version = Rules::version($m);

                if ($name === 'Windows') {
                    $version = self::WINDOWS[$version ?? ''] ?? $version;
                }

                if ($name === 'Windows Phone' && $version === null && str_contains($userAgent, 'WPDesktop')) {
                    $version = '8.1';
                }

                return new Os($name, $version, $family);
            }
        }

        return Os::unknown();
    }

    /**
     * Sec-CH-UA-Platform(-Version) over what the User-Agent claims.
     */
    public function fromHints(ClientHints $hints, Os $os): Os
    {
        $platform = $hints->platform;

        if ($platform === null || strcasecmp($platform, 'Unknown') === 0) {
            return $os;
        }

        $version = $hints->platformVersion;

        return match (strtolower($platform)) {
            'windows' => new Os('Windows', self::windowsFromPlatformVersion($version) ?? ($os->is('Windows') ? $os->version : null), 'windows'),
            'macos' => new Os('macOS', $version ?? ($os->is('macOS') ? $os->version : null), 'apple'),
            'ios' => new Os($os->is('iPadOS') ? 'iPadOS' : 'iOS', $version ?? $os->version, 'apple'),
            'android' => new Os($os->family === 'android' && $os->name !== 'Android' ? $os->name : 'Android', $os->name === 'Android' || $os->name === null ? ($version ?? $os->version) : $os->version, 'android'),
            'chrome os', 'chromium os', 'chromeos' => new Os('ChromeOS', $version ?? $os->version, 'chromeos'),
            'linux' => $os->family === 'linux' ? $os : new Os('Linux', $version, 'linux'),
            'fuchsia' => new Os('Fuchsia', $version, 'other'),
            default => $os->isKnown() ? $os : new Os($platform, $version, 'other'),
        };
    }

    /**
     * Windows reports 13.0.0 and up for 11, 1–10 for 10, 0.1/0.2/0.3 for 7/8/8.1.
     * https://learn.microsoft.com/microsoft-edge/web-platform/how-to-detect-win11
     */
    private static function windowsFromPlatformVersion(?string $version): ?string
    {
        if ($version === null || ! preg_match('/^(\d+)(?:\.(\d+))?/', $version, $m)) {
            return null;
        }

        $major = (int) $m[1];
        $minor = (int) ($m[2] ?? 0);

        return match (true) {
            $major >= 13 => '11',
            $major >= 1 => '10',
            $minor === 3 => '8.1',
            $minor === 2 => '8',
            $minor === 1 => '7',
            default => null,
        };
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private static function rules(): array
    {
        return self::$compiled ??= array_map(fn (array $rule) => [Rules::compile($rule[0]), $rule[1], $rule[2]], self::RULES);
    }
}
