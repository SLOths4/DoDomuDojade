<?php

declare(strict_types=1);

namespace App\Console;

use Throwable;

/**
 * Class responsible for executing commands
 */
final class Kernel
{
    private ConsoleOutput $output;

    public function __construct(
        private readonly CommandRegistry $registry
    ) {
        $this->output = new ConsoleOutput();
    }

    /**
     * Executes a command with given arguments
     * @param array $argv
     * @return int
     */
    public function execute(array $argv): int
    {
        if (count($argv) < 2) {
            $this->showHelp();
            return 0;
        }

        $commandName = $argv[1];
        $arguments = array_slice($argv, 2);

        if ($commandName === 'help') {
            $this->showHelp();
            return 0;
        }

        $command = $this->registry->get($commandName);

        if (!$command) {
            $this->output->error("Unknown command: $commandName");
            return 1;
        }

        if (count($arguments) < $command->getArgumentsCount()) {
            $this->output->error(
                "Missing arguments for command: $commandName\n" .
                "Required: {$command->getArgumentsCount()} argument(s)"
            );
            return 1;
        }

        try {
            $command->execute($arguments, $this->output);
            return 0;
        } catch (Throwable $e) {
            $this->output->error($e->getMessage());
            return 1;
        }
    }

    /**
     * Outputs to console all available commands
     * @return void
     */
    private function showHelp(): void
    {
        $this->output->info("Available commands:");
        $this->output->blank();
        foreach ($this->registry->all() as $command) {
            printf(
                "  %-25s %s\n",
                $command->getName(),
                $command->getDescription()
            );
        }
        $this->output->blank();
    }

}
