<?php
namespace App\Infrastructure\Translation;

interface Translator
{
    public function trans(string $key, array $params = []): string;
}
