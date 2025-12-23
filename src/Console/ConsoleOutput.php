<?php

declare(strict_types=1);

namespace App\Console;

final class ConsoleOutput
{
    // ANSI kolorki
    private const string RESET = "\033[0m";
    private const string RED = "\033[31m";
    private const string GREEN = "\033[32m";
    private const string YELLOW = "\033[33m";
    private const string BLUE = "\033[36m";
    private const string GRAY = "\033[90m";

    public function success(string $message): void
    {
        echo self::GREEN . "✓ " . self::RESET . $message . "\n";
    }

    public function error(string $message): void
    {
        fwrite(STDERR, self::RED . "✗ " . self::RESET . $message . "\n");
    }

    public function info(string $message): void
    {
        echo self::BLUE . "ℹ " . self::RESET . $message . "\n";
    }

    public function warn(string $message): void
    {
        echo self::YELLOW . "⚠ " . self::RESET . $message . "\n";
    }

    public function line(string $message = ""): void
    {
        echo $message . "\n";
    }

    public function blank(): void
    {
        echo "\n";
    }

    public function table(array $headers, array $rows): void
    {
        $columnWidths = array_map(
            fn($header) => strlen((string)$header),
            $headers
        );

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $columnWidths[$i] = max($columnWidths[$i], strlen((string)$cell));
            }
        }

        // Header
        foreach ($headers as $i => $header) {
            echo str_pad((string)$header, $columnWidths[$i]) . "  ";
        }
        echo "\n";
        echo str_repeat("-", array_sum($columnWidths) + count($headers) * 2) . "\n";

        // Rows
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                echo str_pad((string)$cell, $columnWidths[$i]) . "  ";
            }
            echo "\n";
        }
    }
}
