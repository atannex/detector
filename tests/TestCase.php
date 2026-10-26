<?php

declare(strict_types=1);

namespace Atannex\Detector\Tests;

use Atannex\Detector\DetectorServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /** @var array<string, mixed> config overrides for the next app */
    protected array $detector = [];

    protected function getPackageProviders($app): array
    {
        return [DetectorServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

        foreach ($this->detector as $key => $value) {
            $app['config']->set("detector.{$key}", $value);
        }
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Member');
            $table->timestamps();
        });

        (require __DIR__.'/../database/migrations/create_user_devices_table.php')->up();
    }
}
