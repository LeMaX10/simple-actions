<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Console\Commands;

use LeMaX10\SimpleActions\Support\Console\MakeSimpleActionsClassCommand;

class MakeUseCaseCommand extends MakeSimpleActionsClassCommand
{
    protected $signature = 'make:usecase {name : UseCase class name} {--force : Overwrite existing file}';

    protected $description = 'Create a new Simple UseCase class';

    protected function entity(): string
    {
        return 'UseCase';
    }
}
