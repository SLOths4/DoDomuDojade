<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use GuzzleHttp\Psr7\Request;

class MiddlewarePipeline
{
    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function run(Request $request, callable $core): void
    {
        $pipeline = $core;

        foreach (array_reverse($this->middlewares) as $middleware) {
            $currentPipeline = $pipeline;
            $pipeline = function(Request $req) use ($middleware, $currentPipeline): void {
                $middleware->handle($req, $currentPipeline);
            };
        }

        $pipeline($request);
    }
}
