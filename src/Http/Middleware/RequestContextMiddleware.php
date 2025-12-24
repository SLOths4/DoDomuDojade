<?php

namespace App\Http\Middleware;

use App\Http\Context\RequestContext;
use GuzzleHttp\Psr7\Request;

final class RequestContextMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): void
    {
        $path = $request->getUri()->getPath();
        $scope = str_starts_with($path, '/panel/') ? 'admin' : 'user';

        RequestContext::getInstance()->set('scope', $scope);

        $next($request);
    }
}