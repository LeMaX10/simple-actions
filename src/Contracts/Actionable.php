<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Contracts;

/**
 * Интерфейс для реализации Action - Действие.
 *
 * Это объекты реализующие логику конкретного действия, используются для выделения логики работы с данными
 * Один действие = Один объект действия
 *
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
interface Actionable
{
    /**
     * @return static
     */
    public static function make(): static;

    /**
     * @return string
     */
    public static function getName(): string;

    /**
     * @param ...$args
     * @return mixed
     */
    public function run(...$args): mixed;
}
