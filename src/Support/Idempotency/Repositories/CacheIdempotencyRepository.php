<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Support\Idempotency\Repositories;

use Illuminate\Contracts\Cache\Repository;
use LeMaX10\SimpleActions\Contracts\IdempotencyRepository;

class CacheIdempotencyRepository implements IdempotencyRepository
{
    public function __construct(
        private Repository $cache,
        private string $prefix = 'simple-actions:idem',
    ) {}

    public function getResult(string $key): ?array
    {
        $cached = $this->cache->get($this->resultKey($key));

        if (!is_array($cached) || !array_key_exists('value', $cached)) {
            return null;
        }

        return $cached;
    }

    public function acquireProcessing(string $key, int $ttl): bool
    {
        return $this->cache->add($this->processingKey($key), 1, max(1, $ttl));
    }

    public function storeResult(string $key, mixed $value, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl): void
    {
        $payload = [
            'value' => $value,
            'completed_at' => time(),
        ];

        if ($ttl === null) {
            $this->cache->forever($this->resultKey($key), $payload);
            return;
        }

        $this->cache->put($this->resultKey($key), $payload, $ttl);
    }

    public function releaseProcessing(string $key): void
    {
        $this->cache->forget($this->processingKey($key));
    }

    private function resultKey(string $key): string
    {
        return "{$this->prefix}:result:{$key}";
    }

    private function processingKey(string $key): string
    {
        return "{$this->prefix}:processing:{$key}";
    }
}

