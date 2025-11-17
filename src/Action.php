<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions;

use Illuminate\Support\Facades\DB;
use LeMaX10\SimpleActions\Contracts\Actionable;
use LeMaX10\SimpleActions\Contracts\Rememberable;
use LeMaX10\SimpleActions\Contracts\Memorizeable;
use LeMaX10\SimpleActions\Exceptions\ActionHandlerMethodNotFoundException;
use LeMaX10\SimpleActions\Traits\BootAction;
use LeMaX10\SimpleActions\Traits\EventAction;
use LeMaX10\SimpleActions\Traits\Memorize;
use LeMaX10\SimpleActions\Traits\Remember;
use LeMaX10\SimpleActions\Traits\StaticHelpers;

/**
 * Класс Action - Абстрактный объект реализующий логику вспомогательных методов и интерферса (Действие).
 *
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
abstract class Action implements Actionable, Rememberable, Memorizeable
{
    use Remember, BootAction, EventAction, Memorize, StaticHelpers;

    protected const HANDLER_METHOD = 'handle';

    /**
     * @var array<string, bool>
     */
    private static array $handlerMethodExists = [];

    /**
     * @var bool
     */
    protected bool $singleTransaction = false;

    /**
     * @var bool
     */
    protected bool $withoutTransaction = false;

    /**
     * @var array
     */
    protected array $arguments = [];

    /**
     * @var mixed|null
     */
    protected mixed $runResolved = null;

    public function __construct()
    {
        static::booting();
    }

    /**
     * @param ...$args
     * @return mixed
     * @throws ActionHandlerMethodNotFoundException
     */
    public function run(...$args): mixed
    {
        if (!isset(self::$handlerMethodExists[static::class])) {
            self::$handlerMethodExists[static::class] = method_exists($this, self::HANDLER_METHOD);
        }
        
        if (!self::$handlerMethodExists[static::class]) {
            throw new ActionHandlerMethodNotFoundException(static::class);
        }

        $this->arguments = $args;

        if ($this->shouldSkipEvents($this->arguments)) {
            return $this->resolve(...$this->arguments);
        }

        $exception = null;
        try {
            if ($this->fireActionEvent('beforeRun', [$this->arguments]) === false) {
                return false;
            }

            if ($this->fireActionEvent('running', [$this->arguments]) === false) {
                return false;
            }

            $this->runResolved = $this->resolve(...$this->arguments);

            $this->fireActionEvent('ran', [$this->arguments, $this->runResolved], false);

        } catch (\Throwable $e) {
            $exception = $e;

            $this->fireActionEvent('failed', [$this->arguments, $e], false);

            throw $e;
        } finally {
            $this->fireActionEvent('afterRun', [$this->arguments, $this->runResolved ?? null, $exception], false);
        }

        return $this->runResolved;
    }

    public function runIf(bool $condition, ...$args): mixed
    {
        return $condition ? $this->run(...$args) : null;
    }

    public function runUnless(bool $condition, ...$args): mixed
    {
        return $this->runIf(!$condition, ...$args);
    }

    /**
     * @return static
     */
    public function withTransaction(): static
    {
        $clone = clone $this;
        $clone->singleTransaction = true;
        $clone->withoutTransaction = false;

        return $clone;
    }

    /**
     * @return static
     */
    public function withoutTransaction(): static
    {
        $clone = clone $this;
        $clone->withoutTransaction = true;
        $clone->singleTransaction = false;

        return $clone;
    }

    /**
     * @param ...$args
     * @return mixed
     * @throws \Exception
     */
    protected function resolve(...$args): mixed
    {
        return $this->memoize(function () use ($args) {
            $resolver = $this->getResolver(...$args);
    
            if ($this->withoutTransaction === true) {
                return $this->return($resolver);
            }

            // Если включена транзакция
            if ($this->singleTransaction === true) {
                return DB::transaction($resolver);
            }

            return $this->return($resolver);
        }, $args);
    }

    /**
     * @param ...$args
     * @return \Closure
     */
    protected function getResolver(...$args): \Closure
    {
        return fn () => $this->{static::HANDLER_METHOD}(...$args);
    }
}
