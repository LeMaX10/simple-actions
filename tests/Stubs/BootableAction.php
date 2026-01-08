<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Tests\Stubs;

use LeMaX10\SimpleActions\Action;

class BootableAction extends Action
{
    public static int $bootCounter = 0;

    public static function boot(): void
    {
        self::$bootCounter++;
    }

    protected function handle(): string
    {
        return 'booted';
    }
}


