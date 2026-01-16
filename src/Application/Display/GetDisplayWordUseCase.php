<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Application\Word\FetchActiveWordUseCase;
use Psr\Log\LoggerInterface;
use Exception;

readonly class GetDisplayWordUseCase
{
    public function __construct(
        private FetchActiveWordUseCase $fetchActiveWordUseCase,
        private LoggerInterface $logger
    ) {}

    public function execute(): ?array
    {
        try {
            $word = $this->fetchActiveWordUseCase->execute();
        } catch (Exception $e) {
            $this->logger->error("Failed to fetch word", ['error' => $e->getMessage()]);
            return null;
        }

        if (!$word) {
            return null;
        }

        return [
            'word' => $word->word,
            'ipa' => $word->ipa,
            'definition' => $word->definition,
        ];
    }
}
