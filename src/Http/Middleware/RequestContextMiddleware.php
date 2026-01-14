<?php

namespace App\Http\Middleware;

use App\Http\Context\RequestContext;
use GuzzleHttp\Psr7\Request;
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