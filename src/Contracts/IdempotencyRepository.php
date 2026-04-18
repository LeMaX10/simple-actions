<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Contracts;

/**
 * Интерфейс для реализации хранилища блокировок идемпотентности.
 *
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
interface IdempotencyRepository
{
    /**
     * @return array{value:mixed, completed_at?:int}|null
     */
    public function getResult(string $key): ?array;

    public function acquireProcessing(string $key, int $ttl): bool;

    /**
     * @param  \Closure|\DateTimeInterface|\DateInterval|int|null  $ttl
     * @return void
     */
    public function storeResult(string $key, mixed $value, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl): void;

    public function releaseProcessing(string $key): void;
}

