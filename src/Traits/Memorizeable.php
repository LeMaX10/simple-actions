<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Traits;

/**
 * Трейт Memorizeable - мемоизация результатов выполнения в памяти.
 *
 * Позволяет сохранять результаты выполнения Action в памяти на время запроса,
 * избегая повторного выполнения handle() для одинаковых аргументов.
 *
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
trait Memorizeable
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected static array $memoizedResults = [];

    /**
     * @var bool
     */
    protected bool $memoEnabled = false;

    /**
     * @var bool
     */
    protected bool $memoForce = false;

    /**
     * @var string|null
     */
    protected ?string $memoKey = null;

    /**
     * @var bool
     */
    protected bool $memoForceEvents = false;

    /**
     * @var bool
     */
    protected bool $wasFromMemo = false;

    /**
     * @param  bool  $force
     * @param  string|null  $key
     * @param  bool  $forceEvents
     * @return static
     */
    public function memo(bool $force = false, ?string $key = null, bool $forceEvents = false): static
    {
        $clone = clone $this;
        $clone->memoEnabled = true;
        $clone->memoForce = $force;
        $clone->memoKey = $key;
        $clone->memoForceEvents = $forceEvents;

        return $clone;
    }

    /**
     * @param  \Closure  $closure
     * @param  array  $args
     * @return mixed
     */
    protected function memoize(\Closure $closure, array $args): mixed
    {
        if (!$this->memoEnabled) {
            $this->wasFromMemo = false;
            return $closure();
        }

        $key = $this->generateMemoKey($args);
        $class = static::class;

        if ($this->memoForce || !isset(static::$memoizedResults[$class][$key])) {
            $this->wasFromMemo = false;
            static::$memoizedResults[$class][$key] = $closure();
        } else {
            $this->wasFromMemo = true;
        }

        return static::$memoizedResults[$class][$key];
    }

    /**
     * @param  array  $args
     * @return string
     */
    protected function generateMemoKey(array $args): string
    {
        if ($this->memoKey !== null) {
            return $this->memoKey;
        }

        return md5(serialize($args));
    }

    /**
     * @param  array|null  $args
     * @return bool
     */
    public function memoForget(?array $args = null): bool
    {
        $class = static::class;

        if ($args === null) {
            if (isset(static::$memoizedResults[$class])) {
                unset(static::$memoizedResults[$class]);
                return true;
            }
            return false;
        }

        $key = $this->generateMemoKey($args);
        if (isset(static::$memoizedResults[$class][$key])) {
            unset(static::$memoizedResults[$class][$key]);
            return true;
        }

        return false;
    }

    /**
     * @return void
     */
    public static function memoFlush(): void
    {
        if (isset(static::$memoizedResults[static::class])) {
            unset(static::$memoizedResults[static::class]);
        }
    }

    /**
     * @return void
     */
    public static function memoFlushAll(): void
    {
        static::$memoizedResults = [];
    }

    /**
     * @param  array  $args
     * @return bool
     */
    public function isMemoized(array $args): bool
    {
        $key = $this->generateMemoKey($args);
        $class = static::class;

        return isset(static::$memoizedResults[$class][$key]);
    }

    /**
     * @return array
     */
    public static function getMemoizedResults(): array
    {
        return static::$memoizedResults[static::class] ?? [];
    }

    /**
     * @return int
     */
    public static function getMemoizedCount(): int
    {
        return count(static::$memoizedResults[static::class] ?? []);
    }

    /**
     * @return bool
     */
    public function wasResultFromMemo(): bool
    {
        return $this->wasFromMemo;
    }

    /**
     * @return bool
     */
    protected function shouldSkipEventsForMemo(): bool
    {
        return $this->wasFromMemo && !$this->memoForceEvents;
    }

    /**
     * @param  array  $args
     * @return bool
     */
    protected function willResultBeFromMemo(array $args): bool
    {
        if (!$this->memoEnabled || $this->memoForce) {
            return false;
        }

        $key = $this->generateMemoKey($args);
        $class = static::class;

        return isset(static::$memoizedResults[$class][$key]);
    }

    /**
     * @param  array  $args
     * @return bool
     */
    protected function shouldSkipEvents(array $args): bool
    {
        return $this->willResultBeFromMemo($args) && !$this->memoForceEvents;
    }
}

