<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Contracts;

/**
 * Интерфейс для реализации кешируемых объектов.
 *
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
interface Rememberable
{
    /**
     * Метод для внедрения в объект настроек временного кеширование данных
     *
     * @return $this
     */
    public function remember(string $key, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl): static;

    /**
     * Метод для внедрения в объект настроек постоянного кеширование данных
     *
     * @return $this
     */
    public function rememberForever(string $key): static;
}
