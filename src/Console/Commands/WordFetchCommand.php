<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Word\FetchWordUseCase;
use App\Console\Command;
use App\Console\ConsoleOutput;
use Exception;

/**
 * Fetches fresh word
 */
final readonly class WordFetchCommand implements Command
{
    /**
     * @param FetchWordUseCase $useCase
     */
    public function __construct(
        private FetchWordUseCase $useCase
    ) {}

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'word:fetch';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Fetch fresh words from API';
    }

    /**
     * @inheritDoc
     */
    public function getArgumentsCount(): int
    {
        return 0;
    }
}
