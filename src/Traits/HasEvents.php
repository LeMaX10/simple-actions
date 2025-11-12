<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Traits;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Events\NullDispatcher;
use Illuminate\Events\QueuedClosure;
use LeMaX10\SimpleActions\Events\ActionAfterRun;
use LeMaX10\SimpleActions\Events\ActionBeforeRun;
use LeMaX10\SimpleActions\Events\ActionFailed;
use LeMaX10\SimpleActions\Events\ActionRan;
use LeMaX10\SimpleActions\Events\ActionRunning;
use LeMaX10\SimpleActions\Observers\ActionObserver;

trait HasEvents
{
    protected array $dispatchesEvents = [];

    protected static ?Dispatcher $dispatcher = null;

    protected static array $observers = [];

    protected const EVENT_PREFIX = 'simple-action';

    protected static array $eventMap = [
        'beforeRun' => ActionBeforeRun::class,
        'running' => ActionRunning::class,
        'ran' => ActionRan::class,
        'failed' => ActionFailed::class,
        'afterRun' => ActionAfterRun::class,
    ];

    /**
     * @param  ActionObserver|string  $observer
     * @return void
     */
    public static function observe(ActionObserver|string $observer): void
    {
        $observerInstance = is_string($observer) ? app($observer) : $observer;

        static::$observers[static::class][] = $observerInstance;

        foreach (static::$eventMap as $event => $eventClass) {
            if (method_exists($observerInstance, $event)) {
                static::registerActionEvent($event, function ($eventObject) use ($observerInstance, $event) {
                    return $observerInstance->{$event}($eventObject);
                });
            }
        }
    }

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
     * @param  array  $args
     * @param  bool  $halt
     * @return mixed
     */
    protected function fireActionEvent(string $event, array $args = [], bool $halt = true): mixed
    {
        if (empty(static::$dispatcher)) {
            return true;
        }

        $method = $halt ? 'until' : 'dispatch';

        $eventObject = $this->createEventObject($event, $args);

        $result = $this->filterActionEventResults(
            $this->fireCustomActionEvent($event, $method, $eventObject)
        );

        if ($result === false) {
            return false;
        }

        $globalResult = static::$dispatcher->{$method}(
            static::prefixable($event .': '. static::class),
            $eventObject
        );

        if ($globalResult === false) {
            return false;
        }

        return ! empty($result) ? $result : $globalResult;
    }

    /**
     * @param  string  $event
     * @param  array  $args
     * @return object
     */
    protected function createEventObject(string $event, array $args = []): object
    {
        if (isset($this->dispatchesEvents[$event])) {
            return new $this->dispatchesEvents[$event]($this, ...$args);
        }

        if (isset(static::$eventMap[$event])) {
            $eventClass = static::$eventMap[$event];
            return new $eventClass($this, ...$args);
        }

        //fallbacck
        return new class($this, $args) extends ActionEvent {
            public function __construct(public $action, public array $arguments = []) {}
        };
    }

    /**
     * @param  string  $event
     * @param  string  $method
     * @param  object  $eventObject
     * @return mixed|void
     */
    protected function fireCustomActionEvent(string $event, string $method, object $eventObject)
    {
        if (! isset($this->dispatchesEvents[$event])) {
            return;
        }

        $result = static::getEventDispatcher()->$method($eventObject);

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
    public static function running(QueuedClosure|callable|array|string $callback): void
    {
        static::registerActionEvent('running', $callback);
    }

    /**
     * @param  QueuedClosure|callable|array|class-string  $callback
     * @return void
     */
    public static function ran(QueuedClosure|callable|array|string $callback): void
    {
        static::registerActionEvent('ran', $callback);
    }

    /**
     * @param  QueuedClosure|callable|array|class-string  $callback
     * @return void
     */
    public static function failed(QueuedClosure|callable|array|string $callback): void
    {
        static::registerActionEvent('failed', $callback);
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

        foreach (static::$eventMap as $event => $eventClass) {
            static::getEventDispatcher()->forget(static::prefixable($event .': '. static::class));
        }

        // Очищаем observers
        if (isset(static::$observers[static::class])) {
            unset(static::$observers[static::class]);
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
    public static function getEventDispatcher(): ?Dispatcher
    {
        return static::$dispatcher;
    }

    /**
     * @param  Dispatcher|null  $dispatcher
     * @return void
     */
    public static function setEventDispatcher(?Dispatcher $dispatcher): void
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
