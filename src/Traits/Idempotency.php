<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Traits;

use LeMaX10\SimpleActions\Contracts\IdempotencyRepository;
use LeMaX10\SimpleActions\Exceptions\ActionIdempotencyInProgressException;
use LeMaX10\SimpleActions\Support\Idempotency\IdempotencyRepositoryManager;

trait Idempotency
{
    private bool $idempotencyEnabled = false;

    /**
     * @var string|\Closure(...mixed):string|null
     */
    private string|\Closure|null $idempotencyKey = null;

    private bool $idempotencyAutoKey = false;

    private ?string $idempotencyKeyPrefix = null;

    /**
     * @var \Closure|\DateTimeInterface|\DateInterval|int|null
     */
    private \Closure|\DateTimeInterface|\DateInterval|int|null $idempotencyTtl = null;

    private string $idempotencyRepository = 'cache';

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
        $clone->idempotencyAutoKey = false;
        $clone->idempotencyKey = $key;
        $clone->idempotencyTtl = $ttl;

        return $clone;
    }

    /**
     * @param  string|null  $prefix
     * @param  \Closure|\DateTimeInterface|\DateInterval|int|null  $ttl
     * @return static
     */
    public function idempotentAuto(?string $prefix = null, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl = null): static
    {
        $clone = clone $this;
        $clone->idempotencyEnabled = true;
        $clone->idempotencyAutoKey = true;
        $clone->idempotencyKeyPrefix = $prefix ?? static::class;
        $clone->idempotencyKey = null;
        $clone->idempotencyTtl = $ttl;

        return $clone;
    }

    /**
     * @return static
     */
    public function idempotentRepository(string $repository): static
    {
        $clone = clone $this;
        $clone->idempotencyRepository = $repository;

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
        $repository = $this->getIdempotencyRepository();
        $cached = $repository->getResult($baseKey);

        if ($cached !== null && array_key_exists('value', $cached)) {
            return $cached['value'];
        }

        if (!$repository->acquireProcessing($baseKey, $this->idempotencyProcessingTtl)) {
            $cached = $repository->getResult($baseKey);
            if ($cached !== null && array_key_exists('value', $cached)) {
                return $cached['value'];
            }

            throw new ActionIdempotencyInProgressException($baseKey);
        }

        try {
            $result = $closure();
            $repository->storeResult($baseKey, $result, $this->idempotencyTtl);

            return $result;
        } finally {
            $repository->releaseProcessing($baseKey);
        }
    }

    /**
     * @param  array  $args
     * @return string
     */
    protected function resolveIdempotencyKey(array $args): string
    {
        if ($this->idempotencyAutoKey) {
            return $this->generateIdempotencyKey($args);
        }

        if ($this->idempotencyKey === null) {
            throw new \InvalidArgumentException('Idempotency key is required.');
        }

        if ($this->idempotencyKey instanceof \Closure) {
            return (string) call_user_func($this->idempotencyKey, ...$args);
        }

        return $this->idempotencyKey;
    }

    protected function generateIdempotencyKey(array $args): string
    {
        $prefix = $this->idempotencyKeyPrefix ?? static::class;
        $hash = generate_args_hash($args);

        return "{$prefix}:{$hash}";
    }

    protected function getIdempotencyRepository(): IdempotencyRepository
    {
        $container = app();

        $manager = $container->bound(IdempotencyRepositoryManager::class)
            ? $container->make(IdempotencyRepositoryManager::class)
            : new IdempotencyRepositoryManager($container);

        return $manager->driver($this->idempotencyRepository, $this->idempotencyStore);
    }
}
