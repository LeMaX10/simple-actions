<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions;

use Illuminate\Support\ServiceProvider;
use LeMaX10\SimpleActions\Console\Commands\MakeActionCommand;
use LeMaX10\SimpleActions\Console\Commands\MakeUseCaseCommand;

class SimpleActionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Action::setEventDispatcher($this->app['events']);

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeActionCommand::class,
                MakeUseCaseCommand::class,
            ]);
        }
    }
}
