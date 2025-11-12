<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Exceptions;

/**
 * Исключение, которое выбрасывается, когда метод handle не найден в действии
 * 
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam 
 */
class ActionHandlerMethodNotFoundException extends \Exception
{
    public function __construct(string $class)
    {
        parent::__construct(sprintf('Not found handle method in [%s] action', $class));
    }
}
