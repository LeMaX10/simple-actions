<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Events;

/**
 * Событие при ошибке выполнения действия
 * Срабатывает при возникновении исключения в handle()
 * 
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
class ActionFailed extends ActionEvent
{
    /**
     * @var \Throwable
     */
    public \Throwable $exception;

    /**
     * @param  \LeMaX10\SimpleActions\Contracts\Actionable  $action
     * @param  array  $arguments
     * @param  \Throwable  $exception
     */
    public function __construct($action, array $arguments, \Throwable $exception)
    {
        parent::__construct($action, $arguments);
        $this->exception = $exception;
    }
}

