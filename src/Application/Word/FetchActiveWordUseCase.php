<?php

namespace App\Application\Word;

use App\Domain\Word\Word;
use App\Infrastructure\Persistence\WordRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class FetchActiveWordUseCase
{
    public function __construct(
        private LoggerInterface $logger,
        private WordRepository $repository,
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