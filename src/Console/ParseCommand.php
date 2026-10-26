<?php

declare(strict_types=1);

namespace Atannex\Detector\Console;

use Atannex\Detector\Detector;
use Illuminate\Console\Command;

final class ParseCommand extends Command
{
    protected $signature = 'detector:parse
        {user-agent? : The User-Agent (asked for when left out)}
        {--hint=* : A Client Hint header, e.g. --hint="Sec-CH-UA-Platform-Version: \"15.0.0\""}
        {--probe= : A probe cookie value, e.g. "t=5;w=1024;h=1366"}
        {--json : Print JSON}';

    protected $description = 'Show what the detector makes of a User-Agent';

    public function handle(Detector $detector): int
    {
        $userAgent = (string) ($this->argument('user-agent') ?? $this->ask('User-Agent'));
        $headers = [];

        foreach ((array) $this->option('hint') as $hint) {
            [$name, $value] = array_pad(explode(':', (string) $hint, 2), 2, '');
            $headers[trim($name)] = trim($value);
        }

        $device = $detector->parse($userAgent, $headers, $this->option('probe'));

        if ($this->option('json')) {
            $this->line((string) json_encode($device, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $dash = fn (mixed $value) => $value === null || $value === '' ? '—' : (string) $value;

        $this->table(['', ''], [
            ['Label', $device->label()],
            ['Type', $device->type->label()],
            ['Device', $device->brand === null && $device->model === null ? '—' : $device->name()],
            ['OS', $dash($device->os->label()).($device->os->family ? " ({$device->os->family})" : '')],
            ['Browser', $dash($device->browser->label()).($device->browser->family && $device->browser->family !== $device->browser->name ? " (based on {$device->browser->family})" : '')],
            ['Engine', $dash(trim(($device->engine->name ?? '').' '.$device->engine->version->short()))],
            ['App', $dash($device->app)],
            ['WebView', $device->webview ? 'yes' : 'no'],
            ['Architecture', $dash(trim(($device->architecture ?? '').($device->bitness ? " {$device->bitness}-bit" : '')))],
            ['Bot', $device->bot === null ? 'no' : "{$device->bot->name} ({$device->bot->category->value}".($device->bot->producer ? ", {$device->bot->producer}" : '').')'],
            ['Sources', implode(', ', $device->sources) ?: '—'],
        ]);

        return self::SUCCESS;
    }
}
