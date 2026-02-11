<?php

namespace App\Presentation\Http\Middleware;

use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RequestContextMiddleware implements MiddlewareInterface
{
    public function handle(ServerRequestInterface $request, callable $next): \Psr\Http\Message\ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $scope = str_starts_with($path, '/panel/') ? 'admin' : 'user';

        RequestContext::getInstance()->set('scope', $scope);

        return $next($request);
    }
}