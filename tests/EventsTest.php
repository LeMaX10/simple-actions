<?php
declare(strict_types=1);

use Illuminate\Events\Dispatcher;
use LeMaX10\SimpleActions\Action;
use LeMaX10\SimpleActions\Tests\Stubs\CountingAction;
use LeMaX10\SimpleActions\Tests\Stubs\ErrorAction;
use LeMaX10\SimpleActions\Tests\Stubs\HaltingObserver;
use LeMaX10\SimpleActions\Tests\Stubs\RecordingObserver;

beforeEach(function () {
    CountingAction::$runs = 0;
    RecordingObserver::reset();
    CountingAction::memoFlushAll();

    $dispatcher = new Dispatcher(app());

    Action::setEventDispatcher($dispatcher);
    CountingAction::setEventDispatcher($dispatcher);
    ErrorAction::setEventDispatcher($dispatcher);
});

afterEach(function () {
    CountingAction::flushEventListeners();
    ErrorAction::flushEventListeners();
    Action::unsetEventDispatcher();
});

it('вызываем события в правильном порядке', function () {
    $events = [];

    CountingAction::beforeRun(function () use (&$events) {
        $events[] = 'beforeRun';
    });

    CountingAction::running(function () use (&$events) {
        $events[] = 'running';
    });

    CountingAction::ran(function () use (&$events) {
        $events[] = 'ran';
    });

    CountingAction::afterRun(function () use (&$events) {
        $events[] = 'afterRun';
    });

    CountingAction::make()->run('ev');

    expect($events)->toBe(['beforeRun', 'running', 'ran', 'afterRun']);
    expect(CountingAction::$runs)->toBe(1);
});

it('останавливаем выполнение если beforeRun вернул false', function () {
    CountingAction::beforeRun(fn () => false);

    $result = CountingAction::make()->run('stop');

    expect($result)->toBeFalse();
    expect(CountingAction::$runs)->toBe(0);
});

it('останавливаем выполнение если running вернул false', function () {
    CountingAction::running(fn () => false);

    $result = CountingAction::make()->run('stop');

    expect($result)->toBeFalse();
    expect(CountingAction::$runs)->toBe(0);
});

it('поддерживаем observers жизненного цикла', function () {
    CountingAction::observe(new RecordingObserver());

    CountingAction::make()->run('obs');

    expect(RecordingObserver::$events)->toBe([
        'beforeRun',
        'running',
        'ran',
        'afterRun',
    ]);
});

it('отправляем failed и afterRun при ошибке', function () {
    $events = [];

    ErrorAction::failed(function () use (&$events) {
        $events[] = 'failed';
    });

    ErrorAction::afterRun(function () use (&$events) {
        $events[] = 'afterRun';
    });

    expect(fn () => ErrorAction::make()->run())->toThrow(\RuntimeException::class);
    expect($events)->toBe(['failed', 'afterRun']);
});

it('выполняеем без событий', function () {
    $fired = 0;

    CountingAction::ran(function () use (&$fired) {
        $fired++;
    });

    $result = Action::withoutEvents(fn () => CountingAction::make()->run('mute'));

    expect($result)->toBe('processed: mute');
    expect($fired)->toBe(0);
});

it('не вызываем события при возврате из memo', function () {
    $events = 0;

    CountingAction::ran(function () use (&$events) {
        $events++;
    });

    $action = CountingAction::make()->memo();
    $action->run('memo');
    $action->run('memo');

    expect($events)->toBe(1);
    expect(CountingAction::$runs)->toBe(1);
});

it('можем форсировать события даже при memo', function () {
    $events = 0;

    CountingAction::ran(function () use (&$events) {
        $events++;
    });

    $action = CountingAction::make()->memo(forceEvents: true);
    $action->run('memo');
    $action->run('memo');

    expect($events)->toBe(2);
    expect(CountingAction::$runs)->toBe(1);
});

it('observer может останавливать выполнение', function () {
    CountingAction::observe(new HaltingObserver(stopRunning: true));

    $result = CountingAction::make()->run('stop');

    expect($result)->toBeFalse();
    expect(CountingAction::$runs)->toBe(0);
});


