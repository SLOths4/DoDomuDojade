<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\UseCase\Word\FetchWordUseCase;
use App\Console\Command;
use App\Console\ConsoleOutput;
use Exception;

final readonly class WordFetchCommand implements Command
{
    public function __construct(
        private FetchWordUseCase $useCase
    ) {}

    public function execute(array $arguments, ConsoleOutput $output): void
    {
        $output->info("Fetching today's word ...");

        try {
            $this->useCase->execute();
            $output->success("Word fetched successfully!");
        } catch (Exception $e) {
            $output->error("Failed to fetch word: " . $e->getMessage());
        }
    }

    public function getName(): string
    {
        return 'word:fetch';
    }

    public function getDescription(): string
    {
        return 'Fetch fresh words from API';
    }

    public function getArgumentsCount(): int
    {
        return 0;
    }
}
