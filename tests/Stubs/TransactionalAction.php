<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Tests\Stubs;

use Illuminate\Support\Facades\DB;
use LeMaX10\SimpleActions\Action;

class TransactionalAction extends Action
{
    public bool $shouldFail = false;

    protected function handle(string $name = 'row'): array
    {
        DB::table('test_models')->insert(['name' => $name]);

        if ($this->shouldFail) {
            throw new \RuntimeException('transaction failed');
        }

        return [
            'name' => $name,
            'count' => DB::table('test_models')->count(),
        ];
    }

    public function failing(): static
    {
        $clone = clone $this;
        $clone->shouldFail = true;

        return $clone;
    }
}


