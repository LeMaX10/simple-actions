<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Traits;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Трейт Remember - Вспомогательный трейт. Добавляет возможность кешировать результат объекта.
 *
 * Трейт помогает сделать кешируемые результаты объектов. В том числе может быть использован для объектов Действий.
 *
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
trait Remember
{
    /**
     * @var string|null
     */
    private ?string $rememberKey = null;

    /**
     * @var \Closure|\DateTimeInterface|\DateInterval|int|null
     */
    private \Closure|\DateTimeInterface|\DateInterval|int|null $rememberTtl = null;

    /**
     * @var string|null
     */
    private ?string $rememberType = null;

    /**
     * @var array|null
     */
    private ?array $cacheTags = null;

    /**
     * @var string|null
     */
    private ?string $cacheStore = null;

    /**
     * @var \Closure|bool|null
     */
    private \Closure|bool|null $cacheWhen = null;

    /**
     * @var bool
     */
    private bool $autoGenerateKey = false;

    /**
     * @var string|null
     */
    private ?string $cacheKeyPrefix = null;

    /**
     * @var string
     */
    private string $rememberTypeMethod = 'remember';

    /**
     * @var string
     */
    private string $rememberForeverTypeMethod = 'rememberForever';

    /**
     * @return $this
     */
    protected function setRememberKey(string $key): static
    {
        $this->rememberKey = $key;

        return $this;
    }

    /**
     * @return $this
     */
    protected function setRememberTtl(\Closure|\DateTimeInterface|\DateInterval|int|null $ttl): static
    {
        $this->rememberTtl = $ttl;

        return $this;
    }

    /**
     * @return $this
     */
    protected function setRememberType(string $type): static
    {
        $this->rememberType = $type;

        return $this;
    }

    /**
     * @param  string|array|null  $tags
     * @return $this
     */
    protected function setCacheTags(string|array|null $tags): static
    {
        $this->cacheTags = $tags === null ? null : (array) $tags;

        return $this;
    }

    /**
     * @param  string  $store
     * @return $this
     */
    protected function setCacheStore(string $store): static
    {
        $this->cacheStore = $store;

        return $this;
    }

    /**
     * @param  \Closure|bool  $condition
     * @return $this
     */
    protected function setCacheWhen(\Closure|bool $condition): static
    {
        $this->cacheWhen = $condition;

        return $this;
    }

    /**
     * @param  bool  $auto
     * @return $this
     */
    protected function setAutoGenerateKey(bool $auto): static
    {
        $this->autoGenerateKey = $auto;

        return $this;
    }

    /**
     * @param  string  $prefix
     * @return $this
     */
    protected function setCacheKeyPrefix(string $prefix): static
    {
        $this->cacheKeyPrefix = $prefix;

        return $this;
    }

    /**
     * @param  string|array  $tags
     * @return static
     */
    public function tags(string|array $tags): static
    {
        return (clone $this)
            ->setCacheTags($tags);
    }

    /**
     * @param  string  $store
     * @return static
     */
    public function store(string $store): static
    {
        return (clone $this)
            ->setCacheStore($store);
    }

    /**
     * @param  \Closure|bool  $condition
     * @return static
     */
    public function cacheWhen(\Closure|bool $condition): static
    {
        return (clone $this)
            ->setCacheWhen($condition);
    }

    /**
     * @param  \Closure|bool  $condition
     * @return static
     */
    public function cacheUnless(\Closure|bool $condition): static
    {
        return (clone $this)->setCacheWhen(
            is_callable($condition) ? fn (...$args) => !$condition(...$args) : !$condition
        );
    }

    /**
     * @param  string|null  $prefix
     * @return static
     */
    public function rememberAuto(?string $prefix = null, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl = null): static
    {
        $clone = clone $this;
        $clone->autoGenerateKey = true;
        $clone->cacheKeyPrefix = $prefix ?? static::class;

        if ($ttl !== null) {
            $clone->setRememberTtl($ttl);
            $clone->setRememberType($this->rememberTypeMethod);
        } else {
            $clone->setRememberType($this->rememberForeverTypeMethod);
        }

        return $clone;
    }

    /**
     * @return $this
     */
    public function remember(string $key, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl): static
    {
        return (clone $this)
            ->rememberForever($key)
            ->setRememberTtl($ttl)
            ->setRememberType($this->rememberTypeMethod);
    }

    /**
     * @return $this
     */
    public function rememberForever(string $key): static
    {
        return (clone $this)
            ->setRememberKey($key)
            ->setRememberType($this->rememberForeverTypeMethod);
    }

    /**
     * @param  string|null  $key
     * @return bool
     */
    public function forget(?string $key = null): bool
    {
        $cacheKey = $key ?? $this->rememberKey;

        if ($cacheKey === null) {
            return false;
        }

        return $this->getCacheManager()->forget($cacheKey);
    }

    /**
     * @param  \Closure  $closure
     * @param  array  $args
     * @return mixed
     */
    protected function return(\Closure $closure, array $args = []): mixed
    {
        if ($this->rememberType === null) {
            return $closure();
        }
        
        if ($this->cacheWhen !== null) {
            $shouldCache = is_callable($this->cacheWhen)
                ? call_user_func($this->cacheWhen, ...$args)
                : $this->cacheWhen;

            if (!$shouldCache) {
                return $closure();
            }
        }

        if ($this->autoGenerateKey && $this->rememberKey === null) {
            $this->rememberKey = $this->generateCacheKey($args);
        }

        return match ($this->rememberType) {
            $this->rememberTypeMethod => $this->getCacheManager()->remember(
                $this->rememberKey,
                $this->rememberTtl,
                $closure
            ),
            $this->rememberForeverTypeMethod => $this->getCacheManager()->rememberForever(
                $this->rememberKey,
                $closure
            ),
            default => throw new \Exception('Cache type not support'),
        };
    }

    /**
     * @param  array  $args
     * @return string
     */
    protected function generateCacheKey(array $args): string
    {
        $prefix = $this->cacheKeyPrefix ?? static::class;
        $hash = generate_args_hash($args);

        return "{$prefix}:{$hash}";
    }

    /**
     * @return Repository
     * @phpstan-ignore-next-line
     */
    protected function getCacheManager(): Repository
    {
        /** @var \Illuminate\Cache\Repository $cache */
        $cache = $this->cacheStore !== null
            ? Cache::store($this->cacheStore)
            : Cache::driver();

        if (!empty($this->cacheTags)) {
            try {
                /** @phpstan-ignore-next-line */
                $cache = $cache->tags($this->cacheTags);
            } catch (\BadMethodCallException $e) {
                // Драйвер не поддерживает теги - игнорируем
            }
        }

        return $cache;
    }

    /**
     * @return string|null
     */
    public function getCacheKey(): ?string
    {
        return $this->rememberKey;
    }

    /**
     * @param  string|null  $key
     * @return bool
     */
    public function isCached(?string $key = null): bool
    {
        $cacheKey = $key ?? $this->rememberKey;

        if ($cacheKey === null) {
            return false;
        }

        return $this->getCacheManager()->has($cacheKey);
    }
}
