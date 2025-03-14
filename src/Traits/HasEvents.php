<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Traits;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Events\NullDispatcher;
use Illuminate\Events\QueuedClosure;

trait HasEvents
{
    protected $dispatchesEvents = [];

    protected static Dispatcher|null $dispatcher = null;

    protected const EVENT_PREFIX = 'simple-action';

    /**
     * @param  string  $event
     * @param  QueuedClosure|callable|array|class-string  $callback
     * @return void
     */
    protected static function registerActionEvent(string $event, QueuedClosure|callable|array|string $callback): void
    {
        if (!empty(static::$dispatcher)) {
            static::$dispatcher->listen(static::prefixable($event .': '.  static::class), $callback);
        }
    }

    /**
     * @param  string  $event
     * @param  bool  $halt
     * @return mixed
     */
    protected function fireActionEvent(string $event, bool $halt = true): mixed
    {
        if (empty(static::$dispatcher)) {
            return true;
        }

        $method = $halt ? 'until' : 'dispatch';

        $result = $this->filterActionEventResults(
            $this->fireCustomActionEvent($event, $method)
        );

        if ($result === false) {
            return false;
        }

        return ! empty($result) ? $result : static::$dispatcher->{$method}(
            static::prefixable($event .': '. static::class), $this
        );
    }

    /**
     * @param  string  $event
     * @param  string  $method
     * @return mixed|void
     */
    protected function fireCustomActionEvent(string $event, string $method)
    {
        if (! isset($this->dispatchesEvents[$event])) {
            return;
        }

        $result = static::getEventDispatcher()->$method(new $this->dispatchesEvents[$event]($this));

        if (! is_null($result)) {
            return $result;
        }
    }

    /**
     * @param  mixed  $result
     * @return mixed
     */
    protected function filterActionEventResults(mixed $result): mixed
    {
        if (is_array($result)) {
            $result = array_filter($result, function ($response) {
                return ! is_null($response);
            });
        }

        return $result;
    }

    /**
     * @param  QueuedClosure|callable|array|class-string  $callback
     * @return void
     */
    public static function beforeRun(QueuedClosure|callable|array|string $callback): void
    {
        static::registerActionEvent('beforeRun', $callback);
    }

    /**
     * @param  QueuedClosure|callable|array|class-string  $callback
     * @return void
     */
    public static function afterRun(QueuedClosure|callable|array|string $callback): void
    {
        static::registerActionEvent('afterRun', $callback);
    }

    /**
     * @return void
     */
    public static function flushEventListeners(): void
    {
        if (empty(static::$dispatcher)) {
            return;
        }

        $instance = new static;
        foreach ($instance->dispatchesEvents as $event) {
            static::getEventDispatcher()->forget($event);
        }
    }

    /**
     * @return array
     */
    public function dispatchesEvents(): array
    {
        return $this->dispatchesEvents;
    }

    /**
     * @return Dispatcher|null
     */
    public static function getEventDispatcher(): Dispatcher|null
    {
        return static::$dispatcher;
    }

    /**
     * @param  Dispatcher|null  $dispatcher
     * @return void
     */
    public static function setEventDispatcher(Dispatcher|null $dispatcher): void
    {
        static::$dispatcher = $dispatcher;
    }

    /**
     * @return void
     */
    public static function unsetEventDispatcher(): void
    {
        static::$dispatcher = null;
    }

    /**
     * @param  callable  $callback
     * @return mixed
     */
    public static function withoutEvents(callable $callback): mixed
    {
        $dispatcher = static::getEventDispatcher();

        $dispatcher && static::setEventDispatcher(new NullDispatcher($dispatcher));

        try {
            return $callback();
        } finally {
            $dispatcher && static::setEventDispatcher($dispatcher);
        }
    }

    /**
     * @param string $postfix
     * @return string
     */
    protected static function prefixable(string $postfix): string
    {
        return self::EVENT_PREFIX .'.'. $postfix;
    }
}
