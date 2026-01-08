<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Tests\Stubs;

use LeMaX10\SimpleActions\Action;

class CountingAction extends Action
{
    public static int $runs = 0;

    protected function handle(string $value = 'payload'): string
    {
        self::$runs++;

        return "processed: {$value}";
    }
}


