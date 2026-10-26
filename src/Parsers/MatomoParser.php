<?php

declare(strict_types=1);

namespace Atannex\Detector\Parsers;

use Atannex\Detector\ClientHints;
use Atannex\Detector\Contracts\Parser;
use Atannex\Detector\Data\Bot;
use Atannex\Detector\Data\Probe;
use Atannex\Detector\Device;
use Atannex\Detector\Enums\BotCategory;
use Atannex\Detector\Enums\DeviceType;
use Atannex\Detector\Exceptions\InvalidConfiguration;
use DeviceDetector\ClientHints as MatomoHints;
use DeviceDetector\DeviceDetector;

/**
 * The native answer, with matomo/device-detector's device database over
 * it: marketing model names ("Galaxy S23 Ultra" for SM-S918B) and the long
 * tail of brands. Bot categories, Client Hints merging, the probe, apps and
 * WebViews stay the native parser's.
 */
final class MatomoParser implements Parser
{
    public function __construct(private readonly NativeParser $native = new NativeParser)
    {
        if (! class_exists(DeviceDetector::class)) {
            throw new InvalidConfiguration('The matomo driver needs matomo/device-detector: composer require matomo/device-detector');
        }
    }

    public function parse(string $userAgent, ClientHints $hints, ?Probe $probe = null): Device
    {
        $device = $this->native->parse($userAgent, $hints, $probe);

        if ($device->userAgent === '') {
            return $device;
        }

        $server = [];

        foreach ($hints->toHeaders() as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }

        $detector = new DeviceDetector($device->userAgent, MatomoHints::factory($server));
        $detector->parse();

        $changes = ['sources' => [...$device->sources, 'matomo']];

        if ($detector->isBot() && $device->bot === null) {
            $bot = (array) $detector->getBot();
            $changes['bot'] = new Bot((string) ($bot['name'] ?? 'Bot'), BotCategory::Other, $bot['producer']['name'] ?? null, $bot['url'] ?? null);
        }

        $brand = $detector->getBrandName();
        $model = $detector->getModel();
        $type = DeviceType::fromName((string) $detector->getDeviceName());

        // Matomo knows more models; a probe-corrected iPad stays one.
        if (! in_array('probe', $device->sources, true) || $device->model !== 'iPad') {
            if ($brand !== '') {
                $changes['brand'] = $brand;
            }

            if ($model !== '') {
                $changes['model'] = $model;
            }

            if ($type !== null && ($device->type === DeviceType::Unknown || $device->type === DeviceType::Tablet || $device->type === DeviceType::Smartphone)) {
                $changes['type'] = $type;
            }
        }

        return $device->with(...$changes);
    }
}
