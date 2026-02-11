<?php
namespace App\Presentation\Http\Shared;

interface Translator
{
    public function translate(string $key, array $params = []): string;
}
