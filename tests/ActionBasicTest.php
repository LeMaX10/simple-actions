<?php
declare(strict_types=1);

use LeMaX10\SimpleActions\Exceptions\ActionHandlerMethodNotFoundException;
use LeMaX10\SimpleActions\Tests\Stubs\BootableAction;
use LeMaX10\SimpleActions\Tests\Stubs\CountingAction;
use LeMaX10\SimpleActions\Tests\Stubs\NoHandleAction;

beforeEach(function () {
    CountingAction::$runs = 0;
    BootableAction::$bootCounter = 0;
});

it('создаем действие через make и контейнер', function () {
    $action = CountingAction::make();

    expect($action)->toBeInstanceOf(CountingAction::class);
});

it('возвращаем имя класса', function () {
    expect(CountingAction::getName())->toBe('CountingAction');
});

it('выполняем handle с аргументами', function () {
    $result = CountingAction::make()->run('demo');

    expect($result)->toBe('processed: demo');
    expect(CountingAction::$runs)->toBe(1);
});

it('runIf и runUnless управляют выполнением', function () {
    $action = CountingAction::make();

    $run = $action->runIf(true, 'one');
    $skip = $action->runUnless(true, 'two');

    expect($run)->toBe('processed: one');
    expect($skip)->toBeNull();
    expect(CountingAction::$runs)->toBe(1);
});

it('бросаем исключение при отсутствии handle', function () {
    (new NoHandleAction())->run();
})->throws(ActionHandlerMethodNotFoundException::class);

it('выполняем boot только один раз', function () {
    BootableAction::make()->run();
    BootableAction::make()->run();

    expect(BootableAction::$bootCounter)->toBe(1);
});


