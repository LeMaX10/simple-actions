<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Tests\Stubs;

use LeMaX10\SimpleActions\UseCase;

class CompositeUseCase extends UseCase
{
    protected function handle(string $value): array
    {
        return [
            'first' => CountingAction::make()->run($value),
            'second' => CountingAction::make()->run("{$value}-2"),
        ];
    }
}


