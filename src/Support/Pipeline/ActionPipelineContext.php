<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Support\Pipeline;

use LeMaX10\SimpleActions\Action;

class ActionPipelineContext
{
    /**
     * @param  Action  $action
     * @param  array<int, mixed>  $arguments
     */
    public function __construct(
        public readonly Action $action,
        private array $arguments
    ) {}

    /**
     * @return array<int, mixed>
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param  array<int, mixed>  $arguments
     * @return $this
     */
    public function replaceArguments(array $arguments): self
    {
        $this->arguments = array_values($arguments);

        return $this;
    }

    /**
     * @param  int  $index
     * @param  mixed|null  $default
     * @return mixed
     */
    public function argument(int $index, mixed $default = null): mixed
    {
        return $this->arguments[$index] ?? $default;
    }

    /**
     * @param  int  $index
     * @param  mixed  $value
     * @return $this
     */
    public function setArgument(int $index, mixed $value): self
    {
        $this->arguments[$index] = $value;
        ksort($this->arguments);
        $this->arguments = array_values($this->arguments);

        return $this;
    }
}
