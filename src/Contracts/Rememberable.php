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

    /**
     * Метод для внедрения в объект настроек кеширование данных с автоматической генерацией ключа на основе аргументов
     * @param  string|null  $prefix
     * @return static
     */
    public function rememberAuto(?string $prefix = null, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl = null): static;


    /**
     * @param  string|array  $tags
     * @return static
     */
    public function tags(string|array $tags): static;

    /**
     * @param  string  $store
     * @return static
     */
    public function store(string $store): static;

    /**
     * @param  \Closure|bool  $condition
     * @return static
     */
    public function cacheWhen(\Closure|bool $condition): static;

    /**
     * @param  \Closure|bool  $condition
     * @return static
     */
    public function cacheUnless(\Closure|bool $condition): static;
    
    /**
     * @return string|null
     */
    public function getCacheKey(): ?string;

    /**
     * @param  string|null  $key
     * @return bool
     */
    public function isCached(?string $key = null): bool;
}
