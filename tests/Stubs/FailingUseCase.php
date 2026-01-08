<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Tests\Stubs;

use Illuminate\Support\Facades\DB;
use LeMaX10\SimpleActions\UseCase;

class FailingUseCase extends UseCase
{
    protected function handle(string $name = 'case'): void
    {
        DB::table('test_models')->insert(['name' => $name]);

        throw new \RuntimeException('usecase failed');
    }
}


