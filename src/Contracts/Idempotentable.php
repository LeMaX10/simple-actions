<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Contracts;

interface Idempotentable
{
    /**
     * @param  string|\Closure(...mixed):string  $key
     * @param  \Closure|\DateTimeInterface|\DateInterval|int|null  $ttl
     * @return static
     */
    public function idempotent(string|\Closure $key, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl = null): static;

    /**
     * @return static
     */
    public function idempotentStore(string $store): static;

    /**
     * @return static
     */
    public function idempotentProcessingTtl(int $seconds): static;
}
