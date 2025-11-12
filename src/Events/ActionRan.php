<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Events;

/**
 * Событие после успешного выполнения действия
 * Срабатывает сразу после успешного выполнения handle()
 * 
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
class ActionRan extends ActionEvent
{
    /**
     * @var mixed
     */
    public mixed $result;

    /**
     * @param  \LeMaX10\SimpleActions\Contracts\Action  $action
     * @param  array  $arguments
     * @param  mixed  $result
     */
    public function __construct($action, array $arguments, mixed $result)
    {
        parent::__construct($action, $arguments);
        $this->result = $result;
    }
}

