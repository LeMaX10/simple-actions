<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Tests\Stubs;

use LeMaX10\SimpleActions\Action;

class MemoAction extends Action
{
    public static int $executions = 0;
    public static int $multiplier = 2;

    protected function handle(int $value = 1): int
    {
        self::$executions++;

        return $value * self::$multiplier;
    }
}


