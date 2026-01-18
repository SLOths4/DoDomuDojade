<?php
declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface MiddlewareInterface
{
    public function handle(ServerRequestInterface $request, callable $next): ResponseInterface;
}
