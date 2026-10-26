<?php

declare(strict_types=1);

namespace Atannex\Detector;

use Atannex\Detector\Bots\BotVerifier;
use Atannex\Detector\Bots\DnsResolver;
use Atannex\Detector\Console\ParseCommand;
use Atannex\Detector\Console\PruneCommand;
use Atannex\Detector\Contracts\Resolver;
use Atannex\Detector\Http\Middleware\BlockBots;
use Atannex\Detector\Http\Middleware\RequestClientHints;
use Atannex\Detector\Http\Middleware\RestrictDevices;
use Atannex\Detector\Http\Middleware\TrackDevices;
use Atannex\Detector\Tracking\DeviceTracker;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class DetectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/detector.php', 'detector');

        $this->app->singleton(Detector::class, fn (Application $app) => new Detector($app));
        $this->app->alias(Detector::class, 'detector');

        $this->app->bindIf(Resolver::class, DnsResolver::class, true);

        $this->app->singleton(BotVerifier::class, function (Application $app) {
            $config = (array) $app['config']['detector.bots'];
            $store = $app['config']['detector.cache.store'];

            return new BotVerifier(
                $app->make(Resolver::class),
                (array) ($config['verify'] ?? []),
                $app->bound(CacheFactory::class) ? $app->make(CacheFactory::class)->store($store) : null,
                (int) ($config['verify_cache_ttl'] ?? 86400),
            );
        });

        $this->app->singleton(DeviceTracker::class, fn (Application $app) => new DeviceTracker($app->make(Detector::class), $app['events']));
    }

    public function boot(Router $router): void
    {
        $config = (array) $this->app['config']['detector'];

        $router->aliasMiddleware('client-hints', RequestClientHints::class);
        $router->aliasMiddleware('device', RestrictDevices::class);
        $router->aliasMiddleware('block-bots', BlockBots::class);
        $router->aliasMiddleware('track-devices', TrackDevices::class);

        $this->appendToGroups((array) ($config['client_hints']['middleware_groups'] ?? []), RequestClientHints::class);

        if ($config['tracking']['enabled'] ?? false) {
            $this->appendToGroups((array) ($config['tracking']['middleware_groups'] ?? []), TrackDevices::class);
        }

        Request::macro('device', function () {
            /** @var Request $this */
            return app(Detector::class)->detect($this);
        });

        $this->registerBlade($config);

        // The probe is written by JavaScript, so it is never encrypted.
        if ($config['probe']['enabled'] ?? true) {
            EncryptCookies::except([(string) ($config['probe']['cookie'] ?? 'device_probe')]);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/detector.php' => $this->app->configPath('detector.php')], 'detector-config');
            $this->publishesMigrations([__DIR__.'/../database/migrations' => $this->app->databasePath('migrations')], 'detector-migrations');

            $this->commands([ParseCommand::class, PruneCommand::class]);

            AboutCommand::add('Detector', fn () => [
                'Driver' => (string) ($config['driver'] ?? 'native'),
                'Cache' => (string) ($config['cache']['store'] ?? 'memory'),
                'Client Hints' => count((array) ($config['client_hints']['request'] ?? [])).' requested',
                'Probe' => ($config['probe']['enabled'] ?? true) ? 'enabled' : 'disabled',
                'Tracking' => ($config['tracking']['enabled'] ?? false) ? 'enabled' : 'disabled',
            ]);
        }
    }

    /**
     * @param  list<string>  $groups
     */
    private function appendToGroups(array $groups, string $middleware): void
    {
        if ($groups === []) {
            return;
        }

        // The HTTP kernel owns the groups and re-syncs them to the router when
        // it is built, which may happen after this provider boots.
        $append = function (HttpKernel $kernel) use ($groups, $middleware): void {
            foreach ($groups as $group) {
                if (method_exists($kernel, 'appendMiddlewareToGroup') && array_key_exists($group, $kernel->getMiddlewareGroups())) {
                    $kernel->appendMiddlewareToGroup((string) $group, $middleware);
                }
            }
        };

        $this->app->resolved(HttpKernel::class) ? $append($this->app->make(HttpKernel::class)) : $this->app->afterResolving(HttpKernel::class, $append);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function registerBlade(array $config): void
    {
        $device = fn () => app(Detector::class)->detect();

        Blade::if('mobile', fn () => $device()->isMobile());
        Blade::if('tablet', fn () => $device()->isTablet());
        Blade::if('desktop', fn () => $device()->isDesktop());
        Blade::if('handheld', fn () => $device()->isHandheld());
        Blade::if('touch', fn () => $device()->isTouch());
        Blade::if('bot', fn (string ...$names) => $names === [] ? $device()->isBot() : ($device()->bot?->is(...$names) || $device()->bot?->in(...$names)));
        Blade::if('inapp', fn () => $device()->isInApp());
        Blade::if('standalone', fn () => $device()->isStandalone());
        Blade::if('device', fn (string ...$names) => $device()->is(...$names));
        Blade::if('os', fn (string ...$names) => $device()->os->is(...$names));
        Blade::if('browser', fn (string ...$names) => $device()->browser->is(...$names) || $device()->browser->isBasedOn(...$names));

        // <body class="@deviceClasses"> → "device-smartphone os-ios browser-safari touch"
        Blade::directive('deviceClasses', fn (string $expression) => '<?php echo e(app(\\Atannex\\Detector\\Detector::class)->detect()->classes('.($expression === '' ? "''" : $expression).')); ?>');

        $cookie = (string) ($config['probe']['cookie'] ?? 'device_probe');

        // @deviceProbe in <head>: records touch points, screen and display mode for the next request.
        Blade::directive('deviceProbe', fn () => '<?php echo \\Atannex\\Detector\\DetectorServiceProvider::probeScript('.var_export($cookie, true).'); ?>');
    }

    /** @internal */
    public static function probeScript(string $cookie): string
    {
        if (! config('detector.probe.enabled', true)) {
            return '';
        }

        $nonce = app()->bound(\Illuminate\Foundation\Vite::class) ? app(\Illuminate\Foundation\Vite::class)->cspNonce() : null;
        $name = json_encode($cookie);

        $script = <<<JS
        (function(d,w){try{var n=w.navigator,s=w.screen,m=w.matchMedia,v=['t='+(n.maxTouchPoints||0),'w='+s.width,'h='+s.height,'r='+Math.round((w.devicePixelRatio||1)*100)/100,'s='+((m&&m('(display-mode: standalone)').matches)||n.standalone?1:0)].join('|'),k={$name}+'=';if(d.cookie.split('; ').indexOf(k+v)<0){d.cookie=k+v+';path=/;max-age=31536000;samesite=lax'+(location.protocol==='https:'?';secure':'')}}catch(e){}})(document,window);
        JS;

        return '<script'.($nonce ? ' nonce="'.e($nonce).'"' : '').'>'.$script.'</script>';
    }
}
