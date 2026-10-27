<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Quote\Quote;
use App\Domain\Quote\QuoteRepositoryException;
use App\Domain\Quote\QuoteRepositoryInterface;
use App\Infrastructure\Database\DatabaseException;
use App\Infrastructure\Database\DatabaseService;
use DateTimeImmutable;
use PDO;
use Throwable;

/**
 * @inheritDoc
 */
readonly class PDOQuoteRepository implements QuoteRepositoryInterface
{
    private const string TABLE_NAME = 'quote';

    public function __construct(
        private DatabaseService $dbHelper,
        private string          $DATE_FORMAT,
    ) {}

    /**
     * Maps database row to Quote entity.
     * @param array $row
     * @return Quote
     * @throws QuoteRepositoryException
     */
    private function mapRow(array $row): Quote
    {
        try {
            return new Quote(
                (int)$row['id'],
                (string)$row['quote'],
                (string)$row['author'],
                new DateTimeImmutable($row['fetched_on'])
            );
        } catch (Throwable $e) {
            throw QuoteRepositoryException::fetchFailed('Failed to map quote row', $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function add(Quote $quote): int
    {
        try {
            return $this->dbHelper->insert(
                self::TABLE_NAME,
                [
                    'quote'         => [$quote->quote, PDO::PARAM_STR],
                    'author'        => [$quote->author, PDO::PARAM_STR],
                    'fetched_on'    => [$quote->fetchedOn->format($this->DATE_FORMAT), PDO::PARAM_STR],
                ]
            );
        } catch (DatabaseException $e) {
            throw QuoteRepositoryException::persistenceFailed('Failed to insert quote', $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function fetchLatestQuote(): ?Quote
    {
        try {
            $date = date($this->DATE_FORMAT);
            $row = $this->dbHelper->getOne(
                "SELECT * FROM " . self::TABLE_NAME . " WHERE fetched_on = :fetched_on",
                ['fetched_on' => [$date, PDO::PARAM_STR]]
            );

            return $row === null ? null : $this->mapRow($row);
        } catch (DatabaseException $e) {
            throw QuoteRepositoryException::fetchFailed('Failed to fetch latest quote', $e);
        }
    }

}