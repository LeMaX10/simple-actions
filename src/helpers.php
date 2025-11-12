<?php
declare(strict_types=1);

if (!function_exists('action')) {
    /**
     * Хелпер для быстрого создания и выполнения Action
     *
     * @param  string  $actionClass
     * @param  mixed  ...$args
     * @return mixed
     */
    function action(string $actionClass, ...$args): mixed
    {
        return $actionClass::make()->run(...$args);
    }
}

if (!function_exists('usecase')) {
    /**
     * Хелпер для быстрого создания и выполнения UseCase
     *
     * @param  string  $useCaseClass
     * @param  mixed  ...$args
     * @return mixed
     */
    function usecase(string $useCaseClass, ...$args): mixed
    {
        return $useCaseClass::make()->run(...$args);
    }
}

