<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Traits;

/**
 * Трейт BootAction - Обеспечивает одноразовую инициализацию классов
 * 
 * Для использования: необходимо определить protected static function boot() в классе. 
 * Метод boot() будет вызван один раз при первой инициализации класса.
 * 
 * @author Vladimir Pyankov, v@pyankov.pro, RDLTeam
 */
trait BootAction
{
    /**
     * @var array<string, bool>
     */
    protected static array $booted = [];

    /**
     * @return void
     */
    protected static function booting(): void
    {
        if (!isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;

            if (method_exists(static::class, 'boot')) {
                static::boot();
            }
        }
    }
}
