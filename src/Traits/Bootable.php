<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Traits;

trait Bootable
{
    protected static array $booted = [];

    /**
     * @return void
     */
    protected static function booting(): void
    {
        if (! isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;

            method_exists(static::class, 'boot') && static::boot();
        }
    }
}
