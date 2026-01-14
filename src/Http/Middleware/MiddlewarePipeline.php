<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class MiddlewarePipeline
{
    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function run(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        $handler = $next;

        foreach (array_reverse($this->middlewares) as $middleware) {
            $currentHandler = $handler;
            $handler = function(ServerRequestInterface $req) use ($middleware, $currentHandler): ResponseInterface {
                return $middleware->handle($req, $currentHandler);
            };
        }

        $result = $handler($request);

        if ($result === null) {
            $result = new Response(200);
        }

        return $result;
    }
}
