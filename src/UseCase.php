<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions;

/**
 * Базовый класс UseCase - Сценарий агрегирующий множество действий
 * 
 * UseCase это полноценный Action, но предназначенный для координации
 * множества других Actions в единый сценарий выполнения.
 * Поддерживает все возможности Action: события, транзакции, кеширование.
 *
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
abstract class UseCase extends Action
{
    /**
     * По умолчанию UseCase выполняется в транзакции,
     * так как обычно координирует несколько связанных операций
     *
     * @var bool
     */
    protected bool $singleTransaction = true;
}

