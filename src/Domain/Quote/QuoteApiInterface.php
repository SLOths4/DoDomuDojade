<?php
declare(strict_types=1);

namespace App\Domain\Quote;

/**
 * Interface for quote API services
 */
interface QuoteApiInterface
{
    /**
     * @return array{quote: string, author: string}
     */
    public function getQuote(): array;
}
