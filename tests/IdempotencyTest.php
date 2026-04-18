<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use LeMaX10\SimpleActions\Contracts\IdempotencyRepository;
use LeMaX10\SimpleActions\Exceptions\ActionIdempotencyInProgressException;
use LeMaX10\SimpleActions\Support\Idempotency\IdempotencyRepositoryManager;
use LeMaX10\SimpleActions\Tests\Stubs\IdempotentAction;
use LeMaX10\SimpleActions\Tests\Stubs\NullIdempotentAction;

beforeEach(function () {
    IdempotentAction::$runs = 0;
    NullIdempotentAction::$runs = 0;
    Cache::flush();
});

it('повторный вызов с одинаковым ключем не должен выполнять экшен повторно', function () {
    $action = IdempotentAction::make()->idempotent('idem:order:1', 60);

    $first = $action->run('A');
    $second = $action->run('B');

    expect($first)->toBe('run:1:A');
    expect($second)->toBe('run:1:A');
    expect(IdempotentAction::$runs)->toBe(1);
});

it('разные ключи выполняются независимо друг от друга', function () {
    $first = IdempotentAction::make()->idempotent('idem:1')->run('A');
    $second = IdempotentAction::make()->idempotent('idem:2')->run('A');

    expect($first)->toBe('run:1:A');
    expect($second)->toBe('run:2:A');
    expect(IdempotentAction::$runs)->toBe(2);
});

it('поддерживаем вычисление ключа из аргументов замыкания', function () {
    $action = IdempotentAction::make()->idempotent(
        fn (string $value) => "idem:{$value}",
        60
    );

    $first = $action->run('same');
    $second = $action->run('same');
    $third = $action->run('other');

    expect($first)->toBe('run:1:same');
    expect($second)->toBe('run:1:same');
    expect($third)->toBe('run:2:other');
    expect(IdempotentAction::$runs)->toBe(2);
});

it('поддерживаем автоматическую генерацию ключа с префиксом', function () {
    $action = IdempotentAction::make()->idempotentAuto('idem:auto', 60);

    $first = $action->run('A');
    $second = $action->run('A');
    $third = $action->run('B');

    expect($first)->toBe('run:1:A');
    expect($second)->toBe('run:1:A');
    expect($third)->toBe('run:2:B');
    expect(IdempotentAction::$runs)->toBe(2);
});

it('корректно запоминаем null результаты', function () {
    $action = NullIdempotentAction::make()->idempotent('idem:null');

    $first = $action->run();
    $second = $action->run();

    expect($first)->toBeNull();
    expect($second)->toBeNull();
    expect(NullIdempotentAction::$runs)->toBe(1);
});

it('инициируем исключение если действие уже выполняется с тем же ключом', function () {
    $manager = new IdempotencyRepositoryManager(app());
    $manager->driver('cache')->acquireProcessing('idem:busy', 60);

    IdempotentAction::make()
        ->idempotent('idem:busy')
        ->run('A');
})->throws(ActionIdempotencyInProgressException::class);

it('можем переключить репозиторий контроля идемпотентности через менеджер', function () {
    $storage = (object) ['items' => []];
    $manager = new IdempotencyRepositoryManager(app());

    $manager->extend('array', function () use ($storage) {
        return new class($storage) implements IdempotencyRepository {
            public function __construct(private object $storage) {}

            public function getResult(string $key): ?array
            {
                return $this->storage->items["result:{$key}"] ?? null;
            }

            public function acquireProcessing(string $key, int $ttl): bool
            {
                $processingKey = "processing:{$key}";
                if (isset($this->storage->items[$processingKey])) {
                    return false;
                }

                $this->storage->items[$processingKey] = 1;
                return true;
            }

            public function storeResult(string $key, mixed $value, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl): void
            {
                $this->storage->items["result:{$key}"] = ['value' => $value];
            }

            public function releaseProcessing(string $key): void
            {
                unset($this->storage->items["processing:{$key}"]);
            }
        };
    });

    app()->instance(IdempotencyRepositoryManager::class, $manager);

    $action = IdempotentAction::make()
        ->idempotentRepository('array')
        ->idempotent('idem:custom');

    $first = $action->run('A');
    $second = $action->run('B');

    expect($first)->toBe('run:1:A');
    expect($second)->toBe('run:1:A');
    expect(IdempotentAction::$runs)->toBe(1);
    expect($storage->items)->toHaveKey('result:idem:custom');
});

it('Не должно вызывать конфликты при использовании одновременно слоев memo, remember, idempotency', function () {
    $action = IdempotentAction::make()
        ->rememberAuto('remember:mix', 120)
        ->memo()
        ->idempotentAuto('idem:mix', 120);

    $first = $action->run('payload');
    $second = $action->run('payload');

    $anotherInstance = IdempotentAction::make()
        ->rememberAuto('remember:mix', 120)
        ->memo()
        ->idempotentAuto('idem:mix', 120);

    $third = $anotherInstance->run('payload');

    $rememberKey = $action->getCacheKey();
    $idempotencyKey = 'idem:mix:' . generate_args_hash(['payload']);
    $idempotencyResult = (new IdempotencyRepositoryManager(app()))
        ->driver('cache')
        ->getResult($idempotencyKey);

    expect($first)->toBe('run:1:payload');
    expect($second)->toBe('run:1:payload');
    expect($third)->toBe('run:1:payload');
    expect(IdempotentAction::$runs)->toBe(1);

    expect($action->isMemoized(['payload']))->toBeTrue();
    expect($rememberKey)->not->toBeNull();
    expect($action->isCached($rememberKey))->toBeTrue();
    expect($idempotencyResult)->not->toBeNull();
    expect($idempotencyResult['value'])->toBe('run:1:payload');
});
