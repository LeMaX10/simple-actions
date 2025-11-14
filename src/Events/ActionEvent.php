<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Events;

use LeMaX10\SimpleActions\Contracts\Actionable;

/**
 * Абстрактный класс события действия
 * 
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 * @abstract
 */
abstract class ActionEvent
{
    /**
     * @param  Actionable  $action
     * @param  array  $arguments
     */
    public function __construct(
        public Actionable $action,
        public array $arguments = []
    ) {}
}

