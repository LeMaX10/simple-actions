<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Contracts;

/**
 * Интерфейс для реализации мемонизированных(вроде не ошибся в слове :D) объектов.
 *
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
interface Memorizeable
{
 
    public function memo(bool $force = false, ?string $key = null, bool $forceEvents = false): static;

    /**
     * @param  array|null  $args
     * @return bool
     */
    public function memoForget(?array $args = null): bool;

    /**
     * @return void
     */
    public static function memoFlush(): void;

    /**
     * @return void
     */
    public static function memoFlushAll(): void;

    /**
     * @param  array  $args
     * @return bool
     */
    public function isMemoized(array $args): bool;

    /**
     * @return array
     */
    public static function getMemoizedResults(): array;

    /**
     * @return int
     */
    public static function getMemoizedCount(): int;

    /**
     * @return bool
     */
    public function wasResultFromMemo(): bool;
}