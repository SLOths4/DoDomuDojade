<?php

namespace App\Presentation\Http\Shared;

interface ViewRendererInterface
{
    public function render(string $template, array $data = []): string;
}
