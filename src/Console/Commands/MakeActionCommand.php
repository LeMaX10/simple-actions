<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Console\Commands;

use LeMaX10\SimpleActions\Support\Console\MakeSimpleActionsClassCommand;

class MakeActionCommand extends MakeSimpleActionsClassCommand
{
    protected $signature = 'make:action {name : Action class name} {--force : Overwrite existing file}';

    protected $description = 'Create a new Simple Action class';

    protected function entity(): string
    {
        return 'Action';
    }
}
