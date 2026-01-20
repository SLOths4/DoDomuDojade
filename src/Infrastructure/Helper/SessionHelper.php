<?php

namespace App\Infrastructure\Helper;

final readonly class SessionHelper {

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function validateFingerprint(): bool {
        $storedIp = self::get('user_ip_hash');
        $storedAgent = self::get('user_agent_hash');

        $currentIp = hash('sha256', $_SERVER['REMOTE_ADDR']);
        $currentAgent = hash('sha256', $_SERVER['HTTP_USER_AGENT']);

        return $storedIp === $currentIp && $storedAgent === $currentAgent;
    }

    public static function setWithFingerprint(string $key, mixed $value): void {
        self::set($key, $value);
        self::set('user_ip_hash', hash('sha256', $_SERVER['REMOTE_ADDR']));
        self::set('user_agent_hash', hash('sha256', $_SERVER['HTTP_USER_AGENT']));
    }

    public static function set(string $key, mixed $value): void {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void {
        self::start();
        session_unset();
        session_destroy();
    }
}
