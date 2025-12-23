<?php

declare(strict_types=1);

namespace App\Console;

interface Command
{
    public function execute(array $arguments, ConsoleOutput $output): void;

    public function getName(): string;

    public function getDescription(): string;

    public function getArgumentsCount(): int;
}
