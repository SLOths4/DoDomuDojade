<?php
namespace App\Presentation\Http\Context;

use App\Domain\User\User;

final class RequestContext
{
    private static ?self $instance = null;
    private array $data = [];

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

    public function getCurrentUser(): ?User
    {
        return $this->get('user');
    }

    public function setCurrentUser(User $user): void
    {
        $this->set('user', $user);
    }
}