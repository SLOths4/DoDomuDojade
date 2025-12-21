<?php
declare(strict_types=1);

namespace App\Http\Middleware;

interface MiddlewareInterface
{
    public function handle(callable $next): void;
}
