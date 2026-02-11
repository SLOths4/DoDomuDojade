<?php
declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use App\Presentation\Http\Shared\MiddlewareInterface;
use GuzzleHttp\Psr7\Response;
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
        $handler = function(ServerRequestInterface $req) use ($next): ResponseInterface {
            $result = $next($req);
            
            if ($result instanceof ResponseInterface) {
                return $result;
            }

            $response = new Response(200);
            if (is_string($result)) {
                $response->getBody()->write($result);
            }
            
            return $response;
        };

        foreach (array_reverse($this->middlewares) as $middleware) {
            $currentHandler = $handler;
            $handler = function(ServerRequestInterface $req) use ($middleware, $currentHandler): ResponseInterface {
                return $middleware->handle($req, $currentHandler);
            };
        }

        return $handler($request);
    }
}
