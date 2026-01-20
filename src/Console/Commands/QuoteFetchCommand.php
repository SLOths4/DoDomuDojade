<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Quote\FetchQuoteUseCase;
use App\Console\Command;
use App\Console\ConsoleOutput;
use Exception;

final readonly class QuoteFetchCommand implements Command
{
    public function __construct(
        private FetchQuoteUseCase $useCase
    ) {}

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

    public function getName(): string
    {
        return 'quote:fetch';
    }

    public function getDescription(): string
    {
        return 'Fetch fresh quotes from API';
    }

    public function getArgumentsCount(): int
    {
        return 0;
    }
}
