<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Contracts;

/**
 * Интерфейс для реализации идемпотентности объектов.
 * Один объект с одинакомыми аргументами
 *
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
interface Idempotentable
{
    /**
     * @param  string|\Closure(...mixed):string  $key
     * @param  \Closure|\DateTimeInterface|\DateInterval|int|null  $ttl
     * @return static
     */
    public function idempotent(string|\Closure $key, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl = null): static;

    /**
     * @param  string|null  $prefix
     * @param  \Closure|\DateTimeInterface|\DateInterval|int|null  $ttl
     * @return static
     */
    public function idempotentAuto(?string $prefix = null, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl = null): static;

    /**
     * @return static
     */
    public function idempotentRepository(string $repository): static;

    /**
     * @return static
     */
    public function idempotentStore(string $store): static;

    /**
     * @return static
     */
    public function idempotentProcessingTtl(int $seconds): static;
}
