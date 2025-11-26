<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions;

use Illuminate\Support\Facades\DB;
use LeMaX10\SimpleActions\Contracts\Action as ActionContract;
use LeMaX10\SimpleActions\Contracts\Rememberable;
use LeMaX10\SimpleActions\Exceptions\ActionHandlerMethodNotFoundException;
use LeMaX10\SimpleActions\Traits\AsRemembered;
use LeMaX10\SimpleActions\Traits\Bootable;
use LeMaX10\SimpleActions\Traits\HasEvents;
use LeMaX10\SimpleActions\Traits\Memorizeable;

/**
 * Класс Action - Абстрактный объект реализующий логику вспомогательных методов и интерферса (Действие).
 *
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
abstract class Action implements ActionContract, Rememberable
{
    use AsRemembered, Bootable, HasEvents, Memorizeable;

    protected const HANDLER_METHOD = 'handle';

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

    /**
     * @inerhitDoc
     */
    public static function getName(): string
    {
        return class_basename(static::class);
    }

    /**
     * @return static
     */
    public static function make(): static
    {
        return app(static::class);
    }

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
        if (!method_exists($this, self::HANDLER_METHOD)) {
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
            if ($this->withoutTransaction === true) {
                return $this->return($this->getResolver(...$args), args: $args);
            }

            // Если включена транзакция
            if ($this->singleTransaction === true) {
                return DB::transaction(fn () => $this->return($this->getResolver(...$args), args: $args));
            }

            return $this->return($this->getResolver(...$args), args: $args);
        }, $args);
    }

    /**
     * @param ...$args
     * @return \Closure
     */
    protected function getResolver(...$args): \Closure
    {
        return fn () => call_user_func([$this, static::HANDLER_METHOD], ...$args);
    }
}
