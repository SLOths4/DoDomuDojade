<?php

namespace App\Application\Word;

use App\Domain\Word\Word;
use App\Domain\Word\WordRepositoryInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Fetches active word
 */
readonly class FetchActiveWordUseCase
{
    /**
     * @param LoggerInterface $logger
     * @param WordRepositoryInterface $repository
     */
    public function __construct(
        private LoggerInterface   $logger,
        private WordRepositoryInterface $repository,
    ) {}

    /**
     * Fetches today's word
     * @return ?Word
     * @throws Exception
     */
    public function execute(): ?Word
    {
        $this->logger->info("Fetching active word.");
        return $this->repository->fetchLatestWord();
    }
}