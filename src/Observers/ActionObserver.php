<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Observers;

use LeMaX10\SimpleActions\Events\ActionAfterRun;
use LeMaX10\SimpleActions\Events\ActionBeforeRun;
use LeMaX10\SimpleActions\Events\ActionFailed;
use LeMaX10\SimpleActions\Events\ActionRan;
use LeMaX10\SimpleActions\Events\ActionRunning;

/**
 * Абстрактный класс наблюдателя за действиями
 * Позволяет подписываться на события жизненного цикла действий
 * подобно Eloquent Observer
 * 
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
abstract class ActionObserver
{
    /**
     * Обработка события перед началом выполнения
     * Возвращение false остановит выполнение действия
     *
     * @param  ActionBeforeRun  $event
     * @return bool|null
     */
    public function beforeRun(ActionBeforeRun $event): mixed
    {
        return null;
    }

    /**
     * Обработка события непосредственно перед выполнением handle
     * Возвращение false остановит выполнение действия
     *
     * @param  ActionRunning  $event
     * @return bool|null
     */
    public function running(ActionRunning $event): mixed
    {
        return null;
    }

    /**
     * Обработка события после успешного выполнения
     *
     * @param  ActionRan  $event
     * @return void
     */
    public function ran(ActionRan $event): void
    {
        //
    }

    /**
     * Обработка события при ошибке выполнения
     *
     * @param  ActionFailed  $event
     * @return void
     */
    public function failed(ActionFailed $event): void
    {
        //
    }

    /**
     * Обработка события после завершения выполнения
     *
     * @param  ActionAfterRun  $event
     * @return void
     */
    public function afterRun(ActionAfterRun $event): void
    {
        //
    }
}

