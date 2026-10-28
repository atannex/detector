<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('detector.tracking.table', 'user_devices'), function (Blueprint $table) {
            $table->id();
            $table->morphs('authenticatable');
            $table->string('device_id', 64);
            $table->string('name')->nullable();
            $table->string('type', 32);
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('os_name')->nullable();
            $table->string('os_version', 32)->nullable();
            $table->string('browser_name')->nullable();
            $table->string('browser_version', 32)->nullable();
            $table->string('app')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('trusted_at')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['authenticatable_type', 'authenticatable_id', 'device_id'], 'user_devices_owner_device_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('detector.tracking.table', 'user_devices'));
    }
};
