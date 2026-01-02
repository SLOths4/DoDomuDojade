<?php

namespace App\Infrastructure\View;

interface ViewRendererInterface
{
    public function render(string $template, array $data = []): string;
}
