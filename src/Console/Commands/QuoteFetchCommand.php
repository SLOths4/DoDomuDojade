<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Quote\FetchQuoteUseCase;
use App\Console\Command;
use App\Console\ConsoleOutput;
use Exception;

/**
 * Fetches fresh quote
 */
final readonly class QuoteFetchCommand implements Command
{
    /**
     * @param FetchQuoteUseCase $useCase
     */
    public function __construct(
        private FetchQuoteUseCase $useCase
    ) {}

    /**
     * @inheritDoc
     */
    public function execute(array $arguments, ConsoleOutput $output): void
    {
        $output->info("Fetching quotes...");

        try {
            $this->useCase->execute();
            $output->success("Quotes fetched successfully!");
        } catch (Exception $e) {
            $output->error("Failed to fetch quotes: " . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'quote:fetch';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Fetch fresh quotes from API';
    }

    /**
     * @inheritDoc
     */
    public function getArgumentsCount(): int
    {
        return 0;
    }
}
