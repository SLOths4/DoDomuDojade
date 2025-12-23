<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use GuzzleHttp\Psr7\Request;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): void;
}
