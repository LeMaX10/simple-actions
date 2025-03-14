<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions;

use Illuminate\Support\ServiceProvider;

class SimpleActionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Action::setEventDispatcher($this->app['events']);
    }
}
