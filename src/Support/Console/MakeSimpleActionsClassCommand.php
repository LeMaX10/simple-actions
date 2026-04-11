<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Support\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

abstract class MakeSimpleActionsClassCommand extends GeneratorCommand
{
    protected $type = 'Class';

    public function __construct(Filesystem $files)
    {
        $this->type = $this->entity();

        parent::__construct($files);
    }

    protected function getStub()
    {
        return dirname(__DIR__, 3) . '/stubs/' . $this->entityStubName() . '.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\' . Str::pluralStudly($this->entity());
    }

    protected function qualifyClass($name)
    {
        $class = parent::qualifyClass((string) $name);
        $suffix = $this->entity();

        if (Str::endsWith($class, $suffix)) {
            return $class;
        }

        return $class . $suffix;
    }

    protected function buildClass($name)
    {
        return str_replace('DummyBaseClass', $this->entity(), parent::buildClass((string) $name));
    }

    protected function entityStubName(): string
    {
        return strtolower((string) preg_replace('/[^A-Za-z0-9]/', '', $this->entity()));
    }

    abstract protected function entity(): string;
}
