<?php

namespace App\Domain\Word;

use Exception;

/**
 * Desciurbes behavior of word repository
 */
interface WordRepositoryInterface
{
    /**
     * Persists a new word entity.
     *
     * @param Word $word The word entity to add
     * @return bool True if insertion was successful, false otherwise
     * @throws Exception
     */
    public function add(Word $word): bool;

    /**
     * Fetches the latest word for today.
     * Returns the word with the most recent fetched_on date matching today's date.
     *
     * @return Word|null The latest word for today or null if none exists
     * @throws Exception
     */
    public function fetchLatestWord(): ?Word;
}
