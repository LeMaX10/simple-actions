<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Support\Idempotency;

use Illuminate\Contracts\Container\Container;
use LeMaX10\SimpleActions\Contracts\IdempotencyRepository;
use LeMaX10\SimpleActions\Support\Idempotency\Repositories\CacheIdempotencyRepository;

class IdempotencyRepositoryManager
{
    /**
     * @var array<string, \Closure(Container, ?string):IdempotencyRepository>
     */
    private array $drivers = [];

    public function __construct(private Container $container)
    {
        $this->drivers['cache'] = $this->resolveCacheDriver();
    }

    /**
     * @param  \Closure(Container, ?string):IdempotencyRepository  $resolver
     */
    public function extend(string $name, \Closure $resolver): void
    {
        $this->drivers[$name] = $resolver;
    }

    public function driver(string $name, ?string $store = null): IdempotencyRepository
    {
        if (!isset($this->drivers[$name])) {
            throw new \InvalidArgumentException("Unknown idempotency repository driver [{$name}].");
        }

        return ($this->drivers[$name])($this->container, $store);
    }

    private function resolveCacheDriver(): \Closure
    {
        return static function (Container $container, ?string $store): IdempotencyRepository {
            /** @var \Illuminate\Cache\CacheManager $cache */
            $cache = $container->make('cache');
            $repository = $store !== null ? $cache->store($store) : $cache->driver();

            return new CacheIdempotencyRepository($repository);
        };
    }
}

