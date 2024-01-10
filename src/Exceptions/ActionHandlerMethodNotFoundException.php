<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Exceptions;

class ActionHandlerMethodNotFoundException extends \ApplicationException
{
    public function __construct(string $class)
    {
        parent::__construct(sprintf('Not found handle method in [%s] action', $class));
    }
}
