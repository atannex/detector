<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | native   the built-in rules: User-Agent, Client Hints and the probe
    | matomo   matomo/device-detector (composer require it): marketing names
    |          for tens of thousands of models; hints and the probe still apply
    |
    | Or a class implementing Atannex\Detector\Contracts\Parser.
    |
    */

    'driver' => env('DETECTOR_DRIVER', 'native'),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Parsed results are kept in memory per process ('memory' entries, which
    | also serves Octane workers) and, with a store, across requests.
    |
    */

    'cache' => [
        'memory' => 1000,
        'store' => env('DETECTOR_CACHE_STORE'),
        'ttl' => 60 * 60 * 24 * 7,
        'prefix' => 'detector:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Client Hints
    |--------------------------------------------------------------------------
    |
    | The client-hints middleware asks Chromium browsers for these with
    | Accept-CH; they arrive from the next request on. 'critical' ones are
    | sent again on the very first request (Critical-CH), at the cost of a
    | retry. 'middleware_groups' get the middleware automatically.
    |
    */

    'client_hints' => [
        'request' => [
            'Sec-CH-UA-Full-Version-List',
            'Sec-CH-UA-Platform-Version',
            'Sec-CH-UA-Model',
            'Sec-CH-UA-Arch',
            'Sec-CH-UA-Bitness',
            'Sec-CH-UA-Form-Factors',
        ],
        'critical' => [],
        'middleware_groups' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Probe
    |--------------------------------------------------------------------------
    |
    | @deviceProbe prints a few lines of JavaScript that record touch points,
    | screen size and display mode in a cookie. It is how an iPad that says
    | "Macintosh" is told from a Mac, and how an installed PWA is known.
    |
    */

    'probe' => [
        'enabled' => true,
        'cookie' => 'device_probe',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bots
    |--------------------------------------------------------------------------
    |
    | 'empty_user_agent'  a request with no User-Agent counts as a bot
    | 'verify'            the hosts a crawler's IP must reverse-resolve to
    |                     (and resolve back from) for Detector::verifyBot()
    | 'block'             defaults for the block-bots middleware
    |
    */

    'bots' => [
        'empty_user_agent' => true,

        'verify' => [
            'Googlebot' => ['googlebot.com', 'google.com', 'googleusercontent.com'],
            'Googlebot-Image' => ['googlebot.com', 'google.com'],
            'Googlebot-Video' => ['googlebot.com', 'google.com'],
            'Googlebot-News' => ['googlebot.com', 'google.com'],
            'Google-InspectionTool' => ['googlebot.com', 'google.com'],
            'Storebot-Google' => ['googlebot.com', 'google.com'],
            'GoogleOther' => ['googlebot.com', 'google.com'],
            'AdsBot-Google' => ['google.com', 'googlebot.com'],
            'Mediapartners-Google' => ['google.com', 'googlebot.com'],
            'Bingbot' => ['search.msn.com'],
            'Applebot' => ['applebot.apple.com'],
            'YandexBot' => ['yandex.ru', 'yandex.net', 'yandex.com'],
            'Baiduspider' => ['baidu.com', 'baidu.jp'],
            'PetalBot' => ['petalsearch.com', 'aspiegel.com'],
            'Sogou Spider' => ['sogou.com'],
            'SeznamBot' => ['seznam.cz'],
            'Naver Yeti' => ['naver.com'],
            'Amazonbot' => ['crawl.amazonbot.amazon'],
            'Yahoo! Slurp' => ['crawl.yahoo.net'],
            'Facebook' => ['facebook.com', 'fbsv.net'],
        ],

        'verify_cache_ttl' => 60 * 60 * 24,

        'block' => [
            'categories' => ['ai-crawler'],
            'allow' => [],
            'status' => 403,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Device tracking
    |--------------------------------------------------------------------------
    |
    | The track-devices middleware remembers the devices each signed-in user
    | uses (a random id in a long-lived cookie, never a fingerprint) and fires
    | NewDeviceDetected for one it has not seen. Add HasDevices to the model
    | and run the migration (php artisan vendor:publish --tag=detector-migrations).
    |
    */

    'tracking' => [
        'enabled' => env('DETECTOR_TRACKING', false),
        'table' => 'user_devices',
        'model' => \Atannex\Detector\Models\UserDevice::class,
        'cookie' => 'device_id',
        'cookie_minutes' => 60 * 24 * 365 * 5,
        // Seconds between last_seen_at writes for the same device.
        'touch_every' => 300,
        // The first device a user ever signs in from is not "new".
        'notify_first' => false,
        'store_ip' => true,
        'prune_after_days' => 365,
        'middleware_groups' => [],
    ],

];
