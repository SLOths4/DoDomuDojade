<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Application\Word\FetchActiveWordUseCase;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Provides word data formatted for display page
 */
readonly class GetDisplayWordUseCase
{
    /**
     * @param FetchActiveWordUseCase $fetchActiveWordUseCase
     * @param LoggerInterface $logger
     */
    public function __construct(
        private FetchActiveWordUseCase $fetchActiveWordUseCase,
        private LoggerInterface $logger
    ) {}

    /**
     * @return array|null
     */
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
