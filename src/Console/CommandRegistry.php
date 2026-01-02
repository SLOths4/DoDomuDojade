<?php

declare(strict_types=1);

namespace App\Console;

/**
 * Contains a registry of available commands
 */
final class CommandRegistry
{
    /** @var Command[] */
    private array $commands = [];

    /**
     * Adds new command to registry
     * @param Command $command
     * @return void
     */
    public function register(Command $command): void
    {
        $this->commands[$command->getName()] = $command;
    }

    /**
     * Returns command if found in the registry
     * @param string $name
     * @return Command|null
     */
    public function get(string $name): ?Command
    {
        return $this->commands[$name] ?? null;
    }

    /**
     * Returns all commands found in the registry
     * @return Command[]
     */
    public function all(): array
    {
        return $this->commands;
    }

    /**
     * Checks if a command is available in the registry
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->commands[$name]);
    }
}
