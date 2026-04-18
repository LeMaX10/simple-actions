<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Tests\Stubs;

use LeMaX10\SimpleActions\Action;

class IdempotentAction extends Action
{
    public static int $runs = 0;

    protected function handle(string $value = 'x'): string
    {
        self::$runs++;

        return "run:" . self::$runs . ":{$value}";
    }
}
