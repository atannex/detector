<?php

declare(strict_types=1);

namespace Atannex\Detector\Tests\Fixtures;

use Atannex\Detector\Concerns\HasDevices;
use Illuminate\Foundation\Auth\User;

class Member extends User
{
    use HasDevices;

    protected $table = 'members';

    protected $guarded = [];
}
