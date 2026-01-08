<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Tests\Stubs;

use LeMaX10\SimpleActions\Events\ActionBeforeRun;
use LeMaX10\SimpleActions\Events\ActionRunning;
use LeMaX10\SimpleActions\Observers\ActionObserver;

class HaltingObserver extends ActionObserver
{
    public function __construct(
        private bool $stopBefore = false,
        private bool $stopRunning = false,
    ) {}

    public function beforeRun(ActionBeforeRun $event): mixed
    {
        return $this->stopBefore ? false : null;
    }

    public function running(ActionRunning $event): mixed
    {
        return $this->stopRunning ? false : null;
    }
}


