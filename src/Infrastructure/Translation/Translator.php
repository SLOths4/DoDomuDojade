<?php
namespace App\Infrastructure\Translation;

interface Translator
{
    public function translate(string $key, array $params = []): string;
}
