<?php
declare(strict_types=1);

use LeMaX10\SimpleActions\Tests\Stubs\CompositeUseCase;
use LeMaX10\SimpleActions\Tests\Stubs\CountingAction;

beforeEach(function () {
    CountingAction::$runs = 0;
});

it('агрегируем несколько действий', function () {
    $result = CompositeUseCase::make()->run('case');

    expect($result)->toBe([
        'first' => 'processed: case',
        'second' => 'processed: case-2',
    ]);
    expect(CountingAction::$runs)->toBe(2);
});

it('исполняемм usecase через хелпер', function () {
    $result = usecase(CompositeUseCase::class, 'helper');

    expect($result['first'])->toBe('processed: helper');
    expect($result['second'])->toBe('processed: helper-2');
});


