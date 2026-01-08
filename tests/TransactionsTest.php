<?php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LeMaX10\SimpleActions\Tests\Stubs\FailingUseCase;
use LeMaX10\SimpleActions\Tests\Stubs\TransactionalAction;

beforeEach(function () {
    $schema = DB::connection()->getSchemaBuilder();

    if (!$schema->hasTable('test_models')) {
        $schema->create('test_models', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    DB::table('test_models')->truncate();
});

it('оборачиваем выполнение в транзакцию и откатываем при ошибке', function () {
    $action = TransactionalAction::make()->withTransaction()->failing();

    expect(fn () => $action->run('tx'))->toThrow(\RuntimeException::class);
    expect(DB::table('test_models')->count())->toBe(0);
});

it('коммитит изменения при успешном выполнении в транзакции', function () {
    $action = TransactionalAction::make()->withTransaction();

    $result = $action->run('success');

    expect($result)->toBe(['name' => 'success', 'count' => 1]);
    expect(DB::table('test_models')->count())->toBe(1);
});

it('не используем транзакцию с withoutTransaction', function () {
    $action = TransactionalAction::make()->withoutTransaction()->failing();

    expect(fn () => $action->run('no-tx'))->toThrow(\RuntimeException::class);
    expect(DB::table('test_models')->count())->toBe(1);
});

it('usecase по умолчанию выполняем в транзакции', function () {
    expect(fn () => FailingUseCase::make()->run('uc'))->toThrow(\RuntimeException::class);
    expect(DB::table('test_models')->count())->toBe(0);
});


