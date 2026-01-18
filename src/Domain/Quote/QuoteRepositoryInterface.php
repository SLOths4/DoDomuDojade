<?php

namespace App\Domain\Quote;

use Exception;

interface QuoteRepositoryInterface
{
    /**
     * Persists a new quote entity.
     *
     * @param Quote $quote The quote entity to add
     * @return int id
     * @throws Exception
     */
    public function add(Quote $quote): int;

    /**
     * Fetches the latest quote for today.
     * Returns the quote with the most recent fetched_on date matching today's date.
     *
     * @return Quote|null The latest quote for today or null if none exists
     * @throws Exception
     */
    public function fetchLatestQuote(): ?Quote;
}