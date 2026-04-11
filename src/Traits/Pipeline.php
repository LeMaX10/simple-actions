<?php
declare(strict_types=1);

namespace LeMaX10\SimpleActions\Traits;

use LeMaX10\SimpleActions\Contracts\ActionPipe;
use LeMaX10\SimpleActions\Support\Pipeline\ActionPipelineContext;

trait Pipeline
{
    /**
     * @var array<int, class-string<ActionPipe>|\Closure>
     */
    protected array $pipes = [];

    /**
     * @param  array<int, class-string<ActionPipe>|\Closure>  $pipes
     * @return static
     */
    public function through(array $pipes): static
    {
        $clone = clone $this;
        $clone->pipes = array_map(
            fn (mixed $pipe): string|\Closure => $this->normalizePipe($pipe),
            $pipes
        );

        return $clone;
    }

    /**
     * @param  class-string<ActionPipe>|\Closure  $pipe
     * @return static
     */
    public function pipe(string|\Closure $pipe): static
    {
        $clone = clone $this;
        $clone->pipes[] = $this->normalizePipe($pipe);

        return $clone;
    }

    /**
     * @param  \Closure(ActionPipelineContext):mixed  $destination
     * @param  array  $arguments
     * @return mixed
     */
    protected function executeThroughPipeline(\Closure $destination, array $arguments): mixed
    {
        $context = new ActionPipelineContext($this, $arguments);

        if ($this->pipes === []) {
            return $destination($context);
        }

        $next = array_reduce(
            array_reverse($this->pipes),
            fn (\Closure $stack, string|\Closure $pipe): \Closure => fn (ActionPipelineContext $payload): mixed => $this->invokePipe(
                $pipe,
                $stack,
                $payload
            ),
            $destination
        );

        return $next($context);
    }

    /**
     * @param  class-string<ActionPipe>|\Closure  $pipe
     * @param  \Closure(ActionPipelineContext):mixed  $next
     * @param  ActionPipelineContext  $context
     * @return mixed
     */
    protected function invokePipe(string|\Closure $pipe, \Closure $next, ActionPipelineContext $context): mixed
    {
        $resolvedPipe = is_string($pipe) ? app($pipe) : $pipe;

        if ($resolvedPipe instanceof ActionPipe) {
            return $resolvedPipe->handle($context, $next);
        }

        if ($resolvedPipe instanceof \Closure) {
            return $resolvedPipe($context, $next);
        }

        throw new \InvalidArgumentException(
            'Pipeline pipe must be a class-string implementing ActionPipe or a Closure.'
        );
    }

    protected function normalizePipe(mixed $pipe): string|\Closure
    {
        if (is_string($pipe) || $pipe instanceof \Closure) {
            return $pipe;
        }

        throw new \InvalidArgumentException(
            'Pipeline pipe must be a class-string implementing ActionPipe or a Closure.'
        );
    }
}
