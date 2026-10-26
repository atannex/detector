<?php

declare(strict_types=1);

namespace Atannex\Detector;

use Atannex\Detector\Bots\BotVerifier;
use Atannex\Detector\Contracts\Parser;
use Atannex\Detector\Data\Probe;
use Atannex\Detector\Exceptions\InvalidConfiguration;
use Atannex\Detector\Parsers\MatomoParser;
use Atannex\Detector\Parsers\NativeParser;
use Closure;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

/**
 * Detector::detect()            the current request's Device (memoised on the request)
 * Detector::parse($ua, $hints)  any User-Agent
 * Detector::isMobile() …        any Device method, on the current request
 * Detector::verifyBot()         is this Googlebot really Google's?
 * Detector::fake([...])         in tests
 *
 * @mixin Device
 */
final class Detector
{
    private const ATTRIBUTE = 'atannex.detector.device';

    private ?Parser $parser = null;

    /** @var array<string, Closure(Container, array<string, mixed>): Parser> */
    private array $drivers = [];

    /** @var array<string, Device> most recent last */
    private array $memory = [];

    private ?Device $fake = null;

    /**
     * @param  array<string, mixed>|null  $config  fixed settings; null reads config('detector') live
     */
    public function __construct(
        private readonly Container $container,
        private readonly ?array $config = null,
    ) {}

    /**
     * The device behind a request (the current one by default). Parsed once
     * per request and kept on it.
     */
    public function detect(?Request $request = null): Device
    {
        if ($this->fake !== null) {
            return $this->fake;
        }

        $request ??= $this->container->make('request');

        $cached = $request->attributes->get(self::ATTRIBUTE);

        if ($cached instanceof Device) {
            return $cached;
        }

        $device = $this->parse(
            (string) $request->userAgent(),
            ClientHints::fromRequest($request),
            $this->probeFrom($request),
        );

        $request->attributes->set(self::ATTRIBUTE, $device);

        return $device;
    }

    /**
     * @param  ClientHints|array<string, string|list<string>>  $hints  a ClientHints or headers
     */
    public function parse(string $userAgent, ClientHints|array $hints = [], Probe|string|null $probe = null): Device
    {
        $hints = is_array($hints) ? ClientHints::fromHeaders($hints) : $hints;
        $probe = is_string($probe) ? Probe::parse($probe) : $probe;

        $key = md5($userAgent."\0".$hints->key()."\0".($probe === null ? '' : serialize($probe)));

        if (isset($this->memory[$key])) {
            $device = $this->memory[$key];
            // Move to the end: least recently used goes first.
            unset($this->memory[$key]);

            return $this->memory[$key] = $device;
        }

        $store = $this->settings()['cache']['store'] ?? null;

        $device = $store === null
            ? $this->parser()->parse($userAgent, $hints, $probe)
            : $this->container->make(CacheFactory::class)->store($store)->remember(
                ($this->settings()['cache']['prefix'] ?? 'detector:').$key,
                (int) ($this->settings()['cache']['ttl'] ?? 604800),
                fn () => $this->parser()->parse($userAgent, $hints, $probe),
            );

        $this->remember($key, $device);

        return $device;
    }

    /**
     * Verify the current request's bot by reverse and forward DNS: true
     * when it is who it claims, false when it is not, null when it is no
     * bot or there is no list of hosts to check it against.
     */
    public function verifyBot(?Request $request = null): ?bool
    {
        $request ??= $this->container->make('request');
        $device = $this->detect($request);

        return $device->bot === null ? null : $this->container->make(BotVerifier::class)->verify($device->bot, $request->ip());
    }

    /**
     * The hints the client-hints middleware asks for.
     *
     * @return list<string>
     */
    public function clientHints(): array
    {
        return array_values(array_unique((array) ($this->settings()['client_hints']['request'] ?? [])));
    }

    public function parser(): Parser
    {
        return $this->parser ??= $this->resolveDriver((string) ($this->settings()['driver'] ?? 'native'));
    }

    /**
     * Register a driver usable as 'driver' => $name.
     *
     * @param  Closure(Container, array<string, mixed>): Parser  $factory
     */
    public function extend(string $name, Closure $factory): void
    {
        $this->drivers[$name] = $factory;

        if (($this->settings()['driver'] ?? 'native') === $name) {
            $this->parser = null;
            $this->flush();
        }
    }

    public function usingParser(Parser $parser): void
    {
        $this->parser = $parser;
        $this->flush();
    }

    /**
     * Every detect() answers with this device until stopFaking():
     * Detector::fake(['type' => 'tablet', 'os' => 'iPadOS 17']),
     * Detector::fake('Mozilla/5.0 (iPhone; …)') or Detector::fake($device).
     *
     * @param  Device|array<string, mixed>|string  $device
     */
    public function fake(Device|array|string $device = []): Device
    {
        return $this->fake = match (true) {
            $device instanceof Device => $device,
            is_string($device) => $this->parse($device),
            default => Device::make($device),
        };
    }

    public function stopFaking(): void
    {
        $this->fake = null;
    }

    public function isFaking(): bool
    {
        return $this->fake !== null;
    }

    /** Forget the in-memory results (the persistent cache is left alone). */
    public function flush(): void
    {
        $this->memory = [];
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->settings() : data_get($this->settings(), $key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        return $this->config ?? ($this->container->bound('config') ? (array) $this->container->make('config')->get('detector', []) : []);
    }

    public function probeFrom(Request $request): ?Probe
    {
        if (! ($this->settings()['probe']['enabled'] ?? true)) {
            return null;
        }

        $value = $request->cookies->get((string) ($this->settings()['probe']['cookie'] ?? 'device_probe'));

        return is_string($value) ? Probe::parse($value) : null;
    }

    /**
     * Detector::isMobile(), Detector::is('ios'), Detector::label()…
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        $device = $this->detect();

        if (! method_exists($device, $method)) {
            throw new \BadMethodCallException(sprintf('Method %s::%s does not exist.', self::class, $method));
        }

        return $device->{$method}(...$arguments);
    }

    private function remember(string $key, Device $device): void
    {
        $limit = (int) ($this->settings()['cache']['memory'] ?? 1000);

        if ($limit <= 0) {
            return;
        }

        $this->memory[$key] = $device;

        if (count($this->memory) > $limit) {
            array_shift($this->memory);
        }
    }

    private function resolveDriver(string $driver): Parser
    {
        if (isset($this->drivers[$driver])) {
            return ($this->drivers[$driver])($this->container, $this->settings());
        }

        $native = fn () => new NativeParser((bool) ($this->settings()['bots']['empty_user_agent'] ?? true));

        return match (true) {
            $driver === 'native' => $native(),
            $driver === 'matomo' => new MatomoParser($native()),
            class_exists($driver) && is_subclass_of($driver, Parser::class) => $this->container->make($driver),
            default => throw new InvalidConfiguration("Unknown detector driver [{$driver}]."),
        };
    }
}
