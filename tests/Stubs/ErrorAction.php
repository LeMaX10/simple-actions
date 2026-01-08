<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Tests\Stubs;

use LeMaX10\SimpleActions\Action;

class ErrorAction extends Action
{
    protected function handle(): void
    {
        throw new \RuntimeException('fail');
    }
}


