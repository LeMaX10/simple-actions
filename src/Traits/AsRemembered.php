<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Traits;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Трейт AsRemembered - Вспомогательный трейт. Добавляет возможность кешировать результат объекта.
 *
 * Трейт помогает сделает кешируемые результаты объектов. В том числе может быть использован для объектов Действий.
 *
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
trait AsRemembered
{
    /**
     * Ключ кеш данных
     */
    private ?string $rememberKey = null;

    /**
     * Время кеширования
     */
    private \Closure|\DateTimeInterface|\DateInterval|int|null $rememberTtl = null;

    /**
     * Тип кеш данных
     */
    private string|null $rememberType = null;

    private array|null $cacheTags = null;

    /**
     * Тип метода временного кеширования
     */
    private $rememberTypeMethod = 'remember';

    /**
     * Тип метода постоянного кеширования
     */
    private $rememberForeverTypeMethod = 'rememberForever';

    /**
     * Установка ключа кеша
     *
     * @return $this
     */
    protected function setRememberKey(string $key): static
    {
        $this->rememberKey = $key;

        return $this;
    }

    /**
     * Установка времени кеширования
     *
     * @return $this
     */
    protected function setRememberTtl(\Closure|\DateTimeInterface|\DateInterval|int|null $ttl): static
    {
        $this->rememberTtl = $ttl;

        return $this;
    }

    /**
     * Установка типа кеширования
     *
     * @return $this
     */
    protected function setRememberType(string $type): static
    {
        $this->rememberType = $type;

        return $this;
    }

    protected function setCacheTags(string|array|null $tags): static
    {
        $this->cacheTags = $tags === null ? null : (array) $tags;

        return $this;
    }

    public function tags(string|array $tags): static
    {
        return (clone $this)
            ->setCacheTags($tags);
    }

    /**
     * Метод для внедрения в объект настроек временного кеширование данных
     *
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
     * Метод для внедрения в объект настроек постоянного кеширование данных
     *
     * @return $this
     */
    public function rememberForever(string $key): static
    {
        return (clone $this)
            ->setRememberKey($key)
            ->setRememberType($this->rememberForeverTypeMethod);
    }

    /**
     * Прокси метод для возврата данных в зависимости от настроек
     */
    protected function return(\Closure $closure, array $args = []): mixed
    {
        if ($this->rememberType === null) {
            return $closure();
        }

        return match ($this->rememberType) {
            $this->rememberTypeMethod => $this->getCacheManager()->remember($this->rememberKey, $this->rememberTtl, $closure),
            $this->rememberForeverTypeMethod => $this->getCacheManager()->rememberForever($this->rememberKey, $closure),
            default => throw new \Exception('Cache type not support'),
        };
    }

    protected function getCacheManager(): Repository
    {
        return !empty($this->cacheTags) ? Cache::tags($this->cacheTags) : Cache::driver();
    }
}
