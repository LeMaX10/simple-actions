<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Events;

/**
 * Событие непосредственно перед выполнением метода handle
 * Срабатывает после beforeRun, непосредственно перед вызовом handle()
 * 
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
class ActionRunning extends ActionEvent
{
}

