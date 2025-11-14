<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Events;

/**
 * Событие после завершения выполнения действия
 * Срабатывает в самом конце цикла жизни, независимо от успеха или ошибки
 * 
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
class ActionAfterRun extends ActionEvent
{
    /**
     * Результат выполнения действия
     */
    public mixed $result;

    /**
     * Исключение, если произошла ошибка
     */
    public ?\Throwable $exception;

    /**
     * @param  \LeMaX10\SimpleActions\Contracts\Actionable  $action
     * @param  array  $arguments
     * @param  mixed  $result
     * @param  \Throwable|null  $exception
     */
    public function __construct($action, array $arguments, mixed $result = null, ?\Throwable $exception = null)
    {
        parent::__construct($action, $arguments);
        
        $this->result = $result;
        $this->exception = $exception;
    }
}

