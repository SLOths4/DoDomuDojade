<?php
declare(strict_types=1);

if (!function_exists('e')) {
    /**
     * Escapes HTML for output.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
