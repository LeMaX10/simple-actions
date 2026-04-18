<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Tests\Stubs;

use LeMaX10\SimpleActions\Action;

class NullIdempotentAction extends Action
{
    public static int $runs = 0;

    protected function handle(): null
    {
        self::$runs++;

        return null;
    }
}
