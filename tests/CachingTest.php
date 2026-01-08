<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use LeMaX10\SimpleActions\Tests\Stubs\CountingAction;

beforeEach(function () {
    CountingAction::$runs = 0;
    Cache::flush();
});

it('кешируем результат с ttl через remember', function () {
    $action = CountingAction::make()->remember('ttl-key', 60);

    $first = $action->run('one');
    $second = $action->run('one');

    expect($first)->toBe('processed: one');
    expect($second)->toBe('processed: one');
    expect($action->isCached())->toBeTrue();
    expect(CountingAction::$runs)->toBe(1);
});

it('кешируем результат навсегда', function () {
    $action = CountingAction::make()->rememberForever('forever-key');

    $action->run('forever');
    $action->run('forever');

    expect($action->isCached())->toBeTrue();
    expect(CountingAction::$runs)->toBe(1);
});

it('автоматическая генерация ключа с rememberAuto', function () {
    $action = CountingAction::make()->rememberAuto('auto-prefix');

    $first = $action->run('auto');
    $second = $action->run('auto');

    $key = $action->getCacheKey();

    expect($first)->toBe('processed: auto');
    expect($second)->toBe('processed: auto');
    expect($key)->not->toBeNull();
    expect($key)->toStartWith('auto-prefix:');
    expect(CountingAction::$runs)->toBe(1);
});

it('используем условное кеширование через cacheWhen', function () {
    $action = CountingAction::make()
        ->remember('conditional-key', 60)
        ->cacheWhen(fn ($value) => $value > 5);

    $first = $action->run(10);
    $second = $action->run(10);
    $third = $action->run(3);
    $fourth = $action->run(3);

    expect([$first, $second, $third, $fourth])->toBe([
        'processed: 10',
        'processed: 10',
        'processed: 3',
        'processed: 3',
    ]);
    expect(CountingAction::$runs)->toBe(3);
});

it('используем cacheUnless', function () {
    $action = CountingAction::make()
        ->remember('unless-key', 60)
        ->cacheUnless(true);

    $action->run('first');
    $action->run('first');

    expect($action->isCached())->toBeFalse();
    expect(CountingAction::$runs)->toBe(2);
});

it('Используем теги и store', function () {
    $action = CountingAction::make()
        ->tags(['a', 'b'])
        ->store('array')
        ->remember('tagged', 60);

    $action->run('tagged');
    $action->run('tagged');

    expect(CountingAction::$runs)->toBe(1);
});

it('forget и isCached управляем записью', function () {
    $action = CountingAction::make()->remember('forget-key', 60);

    $action->run('to-cache');
    $action->forget();
    $action->run('to-cache');

    expect($action->isCached())->toBeTrue();
    expect(CountingAction::$runs)->toBe(2);
});


