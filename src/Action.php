<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions;

use Illuminate\Support\Facades\DB;
use LeMaX10\SimpleActions\Contracts\Action as ActionContract;
use LeMaX10\SimpleActions\Contracts\Rememberable;
use LeMaX10\SimpleActions\Exceptions\ActionHandlerMethodNotFoundException;
use LeMaX10\SimpleActions\Traits\AsRemembered;

/**
 * Класс Action - Абстрактный объект реализующий логику вспомогательных методов и интерферса (Действие).
 *
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
abstract class Action implements ActionContract, Rememberable
{
    use AsRemembered;

    /**
     * Событие экшена в единую транзакцию.
     * @var bool
     */
    protected bool $singleTransaction = false;

    /**
     *
     */
    protected const HANDLER_METHOD = 'handle';

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

        return $this->resolve(...$args);
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
     * @param ...$args
     * @return mixed
     * @throws \Exception
     */
    protected function resolve(...$args): mixed
    {
        if ($this->singleTransaction === true) {
            return DB::transaction($this->getResolver(...$args));
        }

        return $this->return($this->getResolver(...$args));
    }

    protected function getResolver(...$args): \Closure
    {
        return fn () => call_user_func([$this, static::HANDLER_METHOD], ...$args);
    }
}
