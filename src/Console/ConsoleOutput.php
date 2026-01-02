<?php

declare(strict_types=1);

namespace App\Console;

/**
 * Defines console output helper functions
 */
final class ConsoleOutput
{
    /** @var string */
    private const string RESET = "\033[0m";
    /** @var string ANIS red color */
    private const string RED = "\033[31m";
    /** @var string ANSI green color */
    private const string GREEN = "\033[32m";
    /** @var string ANSI yellow color */
    private const string YELLOW = "\033[33m";
    /** @var string ANSI blue color */
    private const string BLUE = "\033[36m";
    /** @var string ANSI grey color */
    private const string GRAY = "\033[90m";

    /**
     * Outputs success to console
     * @param string $message
     * @return void
     */
    public function success(string $message): void
    {
        echo self::GREEN . "✓ " . self::RESET . $message . "\n";
    }

    public function error(string $message): void
    {
        fwrite(STDERR, self::RED . "✗ " . self::RESET . $message . "\n");
    }

    /**
     * Outputs info to console
     * @param string $message
     * @return void
     */
    public function info(string $message): void
    {
        echo self::BLUE . "ℹ " . self::RESET . $message . "\n";
    }

    /**
     * Outputs warning to console
     * @param string $message
     * @return void
     */
    public function warn(string $message): void
    {
        echo self::YELLOW . "⚠ " . self::RESET . $message . "\n";
    }

    /**
     * Outputs message to console
     * @param string $message
     * @return void
     */
    public function line(string $message = ""): void
    {
        echo $message . "\n";
    }

    /**
     * Outputs new line to console
     * @return void
     */
    public function blank(): void
    {
        echo "\n";
    }

    /**
     * Outputs a table to console
     * @param array $headers
     * @param array $rows
     * @return void
     */
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
