<?php

declare(strict_types=1);

namespace App\Console;

/**
 * Describes behavior of a command
 */
interface Command
{
    /**
     * Executes command
     * @param array $arguments
     * @param ConsoleOutput $output
     * @return void
     */
    public function execute(array $arguments, ConsoleOutput $output): void;

    /**
     * Returns command name
     * @return string
     */
    public function getName(): string;

    /**
     * Returns command description
     * @return string
     */
    public function getDescription(): string;

    /**
     * Returns required parameters number
     * @return int number of parameters
     */
    public function getArgumentsCount(): int;
}
