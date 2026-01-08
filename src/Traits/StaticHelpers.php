<?php

declare(strict_types=1);

namespace LeMaX10\SimpleActions\Traits;

trait StaticHelpers
{   
    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return class_basename(static::class);
    }

    /**
     * @return static
     */
    public static function make(): static
    {
        return app(static::class);
    }
}