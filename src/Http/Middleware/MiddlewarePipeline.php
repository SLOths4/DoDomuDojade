<?php
declare(strict_types=1);

namespace App\Http\Middleware;

class MiddlewarePipeline
{
    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function run(callable $core): void
    {
        $pipeline = $core;

        foreach (array_reverse($this->middlewares) as $middleware) {
            $pipeline = fn() => $middleware->handle($pipeline);
        }

        $pipeline();
    }
}
