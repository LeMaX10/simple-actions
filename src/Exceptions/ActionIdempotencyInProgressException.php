<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Exceptions;

class ActionIdempotencyInProgressException extends \RuntimeException
{
    public function __construct(string $key)
    {
        parent::__construct("Action with idempotency key [{$key}] is already in progress.");
    }
}
