<?php

declare(strict_types=1);

namespace Atannex\Detector\Console;

use Atannex\Detector\Models\UserDevice;
use Illuminate\Console\Command;

final class PruneCommand extends Command
{
    protected $signature = 'detector:prune {--days= : Remove devices unseen for this many days (default: detector.tracking.prune_after_days)}';

    protected $description = 'Remove tracked devices that have not been seen for a while';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('detector.tracking.prune_after_days', 365));

        if ($days < 1) {
            $this->error('--days must be at least 1.');

            return self::FAILURE;
        }

        /** @var class-string<UserDevice> $model */
        $model = config('detector.tracking.model', UserDevice::class);

        $count = $model::query()->stale($days)->delete();

        $this->info("Removed {$count} device(s) unseen for {$days} days.");

        return self::SUCCESS;
    }
}
