<?php

namespace App\Application\UseCase\Word;

use App\Domain\Entity\Word;
use App\Infrastructure\Repository\WordRepository;
use App\Infrastructure\Service\WordApiService;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

readonly class FetchWordUseCase
{
    public function __construct(
        private LoggerInterface $logger,
        private WordApiService $apiService,
        private WordRepository $repository
    ) {}

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        if ($this->repository->fetchLatestWord()) {
            $this->logger->warning("Word has been already fetched today");
            return;
        }
        $this->logger->info("Starting daily word fetch");
        $data = $this->apiService->getWord();
        $word = new Word(
            null,
            $data['word'],
            $data['ipa'],
            $data['definition'],
            new DateTimeImmutable(),
        );
        $this->repository->add($word);
        $this->logger->info("Word saved successfully");
    }
}