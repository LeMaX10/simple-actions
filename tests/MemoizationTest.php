<?php
declare(strict_types=1);

use LeMaX10\SimpleActions\Tests\Stubs\MemoAction;

beforeEach(function () {
    MemoAction::$executions = 0;
    MemoAction::$multiplier = 2;
    MemoAction::memoFlushAll();
});

it('мемоизируем результат для одинаковых аргументов', function () {
    $action = MemoAction::make()->memo();

    $first = $action->run(5);
    $second = $action->run(5);

    expect($first)->toBe(10);
    expect($second)->toBe(10);
    expect(MemoAction::$executions)->toBe(1);
});

it('не смешиваем результаты для разных аргументов', function () {
    $action = MemoAction::make()->memo();

    $one = $action->run(2);
    $two = $action->run(6);

    expect($one)->toBe(4);
    expect($two)->toBe(12);
    expect(MemoAction::$executions)->toBe(2);
});

it('используем заданный ключ для мемоизации', function () {
    $action = MemoAction::make()->memo(key: 'fixed');

    $first = $action->run(1);
    $second = $action->run(99);

    expect($first)->toBe(2);
    expect($second)->toBe(2);
    expect(MemoAction::$executions)->toBe(1);
});

it('force обновляем значение', function () {
    $action = MemoAction::make()->memo();

    $first = $action->run(3);
    MemoAction::$multiplier = 3;
    $forced = MemoAction::make()->memo(force: true)->run(3);
    $after = $action->run(3);

    expect($first)->toBe(6);
    expect($forced)->toBe(9);
    expect($after)->toBe(9);
    expect(MemoAction::$executions)->toBe(2);
});

it('memoForget удаляем сохраненный результат', function () {
    $action = MemoAction::make()->memo();

    $action->run(5);
    $forgotten = $action->memoForget([5]);
    $action->run(5);

    expect($forgotten)->toBeTrue();
    expect(MemoAction::$executions)->toBe(2);
});

it('memoFlush очищаем данные класса', function () {
    $action = MemoAction::make()->memo();

    $action->run(1);
    $action->run(2);

    expect(MemoAction::getMemoizedCount())->toBe(2);

    MemoAction::memoFlush();

    expect(MemoAction::getMemoizedCount())->toBe(0);
});

it('memoFlushAll очищаем все классы', function () {
    $action = MemoAction::make()->memo();
    $action->run(1);

    MemoAction::memoFlushAll();

    expect(MemoAction::getMemoizedCount())->toBe(0);
});

it('проверяем статус мемоизации', function () {
    $action = MemoAction::make()->memo();

    expect($action->isMemoized([7]))->toBeFalse();

    $action->run(7);
    $action->run(7);

    expect($action->isMemoized([7]))->toBeTrue();
    expect($action->wasResultFromMemo())->toBeTrue();

    $action = MemoAction::make()->memo(force: true);
    $action->run(7);

    expect($action->wasResultFromMemo())->toBeFalse();
});


