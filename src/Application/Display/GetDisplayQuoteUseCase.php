<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Application\Quote\FetchActiveQuoteUseCase;
use Psr\Log\LoggerInterface;
use Exception;

readonly class GetDisplayQuoteUseCase
{
    public function __construct(
        private FetchActiveQuoteUseCase $fetchActiveQuoteUseCase,
        private LoggerInterface $logger
    ) {}

    public function execute(): ?array
    {
        try {
            $quote = $this->fetchActiveQuoteUseCase->execute();
        } catch (Exception $e) {
            $this->logger->error("Failed to fetch quote", ['error' => $e->getMessage()]);
            return null;
        }

        if (!$quote) {
            return null;
        }

        return [
            'from' => $quote->author,
            'quote' => $quote->quote,
        ];
    }
}
