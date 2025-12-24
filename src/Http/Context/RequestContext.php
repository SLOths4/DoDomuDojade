<?php
namespace App\Http\Context;

final class RequestContext
{
    private static ?self $instance = null;
    private array $data = [];

    private function __construct() {}

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}