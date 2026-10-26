<?php

declare(strict_types=1);

namespace Atannex\Detector\Events;

use Atannex\Detector\Data\Bot;
use Illuminate\Http\Request;

final class BotBlocked
{
    public function __construct(
        public readonly Request $request,
        public readonly Bot $bot,
    ) {}
}
