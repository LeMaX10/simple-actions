<?php
declare(strict_types=1);

use LeMaX10\SimpleActions\Action;
use LeMaX10\SimpleActions\Contracts\Actionable;
use LeMaX10\SimpleActions\UseCase;

it('декларируем дженерик результата в Actionable', function () {
    $interfaceDoc = (new ReflectionClass(Actionable::class))->getDocComment();
    $runDoc = (new ReflectionMethod(Actionable::class, 'run'))->getDocComment();

    expect($interfaceDoc)->not->toBeFalse();
    expect($interfaceDoc)->toContain('@template TResult');

    expect($runDoc)->not->toBeFalse();
    expect($runDoc)->toContain('@return TResult');
});

it('пробрасываем дженерик результата в Action и UseCase', function () {
    $actionDoc = (new ReflectionClass(Action::class))->getDocComment();
    $useCaseDoc = (new ReflectionClass(UseCase::class))->getDocComment();

    expect($actionDoc)->not->toBeFalse();
    expect($actionDoc)->toContain('@template TResult');
    expect($actionDoc)->toContain('@implements Actionable<TResult>');

    expect($useCaseDoc)->not->toBeFalse();
    expect($useCaseDoc)->toContain('@template TResult');
    expect($useCaseDoc)->toContain('@extends Action<TResult>');
});

it('типизируем phpdoc у хелперов action и usecase', function () {
    $actionHelper = new ReflectionFunction('action');
    $actionHelperDoc = $actionHelper->getDocComment();
    $useCaseHelperDoc = (new ReflectionFunction('usecase'))->getDocComment();
    $actionWithHelperDoc = (new ReflectionFunction('action_with'))->getDocComment();

    expect($actionHelperDoc)->not->toBeFalse();

    // В приложении с Laravel глобальная функция action() может быть уже объявлена фреймворком.
    // Если это наша helper-функция, проверяем generic phpdoc; иначе проверяем только её существование.
    if ($actionHelper->getFileName() === __DIR__ . '/../src/helpers.php') {
        expect($actionHelperDoc)->toContain('@template TResult');
        expect($actionHelperDoc)->toContain('@return TResult|false');
    }

    expect($useCaseHelperDoc)->not->toBeFalse();
    expect($useCaseHelperDoc)->toContain('@template TResult');
    expect($useCaseHelperDoc)->toContain('@return TResult|false');

    expect($actionWithHelperDoc)->not->toBeFalse();
    expect($actionWithHelperDoc)->toContain('@template TResult');
    expect($actionWithHelperDoc)->toContain('@return TResult|false');
});
