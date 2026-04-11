<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Traits;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use LeMaX10\SimpleActions\Exceptions\ActionIdempotencyInProgressException;

trait Idempotency
{
    private bool $idempotencyEnabled = false;

    /**
     * @var string|\Closure(...mixed):string|null
     */
    private string|\Closure|null $idempotencyKey = null;

    /**
     * @var \Closure|\DateTimeInterface|\DateInterval|int|null
     */
    private \Closure|\DateTimeInterface|\DateInterval|int|null $idempotencyTtl = null;

    private ?string $idempotencyStore = null;

    private int $idempotencyProcessingTtl = 30;

    /**
     * @param  string|\Closure(...mixed):string  $key
     * @param  \Closure|\DateTimeInterface|\DateInterval|int|null  $ttl
     * @return static
     */
    public function idempotent(string|\Closure $key, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl = null): static
    {
        $clone = clone $this;
        $clone->idempotencyEnabled = true;
        $clone->idempotencyKey = $key;
        $clone->idempotencyTtl = $ttl;

        return $clone;
    }

    /**
     * @return static
     */
    public function idempotentStore(string $store): static
    {
        $clone = clone $this;
        $clone->idempotencyStore = $store;

        return $clone;
    }

    /**
     * Время жизни флага "в процессе выполнения" (секунды).
     *
     * @return static
     */
    public function idempotentProcessingTtl(int $seconds): static
    {
        $clone = clone $this;
        $clone->idempotencyProcessingTtl = max(1, $seconds);

        return $clone;
    }

    /**
     * @param  \Closure  $closure
     * @param  array  $args
     * @return mixed
     */
    protected function executeIdempotent(\Closure $closure, array $args): mixed
    {
        if (!$this->idempotencyEnabled) {
            return $closure();
        }

        $baseKey = $this->resolveIdempotencyKey($args);
        $resultKey = $this->idempotencyResultKey($baseKey);
        $processingKey = $this->idempotencyProcessingKey($baseKey);
        $cache = $this->getIdempotencyCacheManager();
        $cached = $cache->get($resultKey);

        if (is_array($cached) && array_key_exists('value', $cached)) {
            return $cached['value'];
        }

        if (!$cache->add($processingKey, 1, $this->idempotencyProcessingTtl)) {
            $cached = $cache->get($resultKey);
            if (is_array($cached) && array_key_exists('value', $cached)) {
                return $cached['value'];
            }

            throw new ActionIdempotencyInProgressException($baseKey);
        }

        try {
            $result = $closure();
            $payload = [
                'value' => $result,
                'completed_at' => time(),
                'action' => static::class,
            ];

            if ($this->idempotencyTtl === null) {
                $cache->forever($resultKey, $payload);
            } else {
                $cache->put($resultKey, $payload, $this->idempotencyTtl);
            }

            return $result;
        } finally {
            $cache->forget($processingKey);
        }
    }

    /**
     * @param  array  $args
     * @return string
     */
    protected function resolveIdempotencyKey(array $args): string
    {
        if ($this->idempotencyKey === null) {
            throw new \InvalidArgumentException('Idempotency key is required.');
        }

        if ($this->idempotencyKey instanceof \Closure) {
            return (string) call_user_func($this->idempotencyKey, ...$args);
        }

        return $this->idempotencyKey;
    }

    protected function idempotencyResultKey(string $baseKey): string
    {
        return "simple-actions:idem:result:{$baseKey}";
    }

    protected function idempotencyProcessingKey(string $baseKey): string
    {
        return "simple-actions:idem:processing:{$baseKey}";
    }

    /**
     * @return Repository
     */
    protected function getIdempotencyCacheManager(): Repository
    {
        /** @var \Illuminate\Cache\Repository $cache */
        $cache = $this->idempotencyStore !== null
            ? Cache::store($this->idempotencyStore)
            : Cache::driver();

        return $cache;
    }
}
