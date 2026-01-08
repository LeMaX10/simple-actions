<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Tests\Stubs;

use LeMaX10\SimpleActions\Events\ActionAfterRun;
use LeMaX10\SimpleActions\Events\ActionBeforeRun;
use LeMaX10\SimpleActions\Events\ActionFailed;
use LeMaX10\SimpleActions\Events\ActionRan;
use LeMaX10\SimpleActions\Events\ActionRunning;
use LeMaX10\SimpleActions\Observers\ActionObserver;

class RecordingObserver extends ActionObserver
{
    public static array $events = [];

    public function beforeRun(ActionBeforeRun $event): mixed
    {
        self::$events[] = 'beforeRun';
        return null;
    }

    public function running(ActionRunning $event): mixed
    {
        self::$events[] = 'running';
        return null;
    }

    public function ran(ActionRan $event): void
    {
        self::$events[] = 'ran';
    }

    public function failed(ActionFailed $event): void
    {
        self::$events[] = 'failed';
    }

    public function afterRun(ActionAfterRun $event): void
    {
        self::$events[] = 'afterRun';
    }

    public static function reset(): void
    {
        self::$events = [];
    }
}


