<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Contracts;

interface Pipeable
{
    /**
     * @param  array<int, class-string<ActionPipe>|\Closure>  $pipes
     * @return static
     */
    public function through(array $pipes): static;

    /**
     * @param  class-string<ActionPipe>|\Closure  $pipe
     * @return static
     */
    public function pipe(string|\Closure $pipe): static;
}
