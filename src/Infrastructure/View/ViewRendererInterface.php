<?php

namespace App\Infrastructure\View;

use Psr\Http\Message\ResponseInterface;

interface ViewRendererInterface
{
    public function render(string $template, array $data = []): string;
}
