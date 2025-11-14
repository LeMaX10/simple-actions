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

if (!function_exists('action_with')) {
    /**
     * Хелпер для создания Action с конфигурацией
     *
     * Позволяет сконфигурировать Action перед выполнением через callback.
     * Полезно для добавления мемоизации, кеширования и других опций.
     *
     * @param  string  $actionClass
     * @param  \Closure  $configure
     * @param  mixed  ...$args
     * @return mixed
     */
    function action_with(string $actionClass, \Closure $configure, ...$args): mixed
    {
        $action = $actionClass::make();
        $action = $configure($action);

        return $action->run(...$args);
    }
}

if (!function_exists('generate_args_hash')) {
    /**
     * Генерирует MD5 хеш из массива аргументов
     * 
     * @param  array  $args
     * @return string
     */
    function generate_args_hash(array $args): string
    {
        try {
            $serialized = json_encode($args, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $serialized = serialize($args);
        }

        return md5($serialized);
    }
}

