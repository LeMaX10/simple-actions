<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Events;

use LeMaX10\SimpleActions\Contracts\Action;

/**
 * Абстрактный класс события действия
 * 
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 * @abstract
 */
abstract class ActionEvent
{
    /**
     * @var Action
     */
    public Action $action;

    /**
     * @var array
     */
    public array $arguments;

    /**
     * @param  Action  $action
     * @param  array  $arguments
     */
    public function __construct(Action $action, array $arguments = [])
    {
        $this->action = $action;
        $this->arguments = $arguments;
    }
}

