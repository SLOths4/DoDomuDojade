<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Quote\Quote;
use App\Domain\Quote\QuoteRepositoryInterface;
use App\Infrastructure\Database\DatabaseService;
use DateTimeImmutable;
use Exception;
use PDO;

/**
 * @inheritDoc
 */
readonly class PDOQuoteRepository implements QuoteRepositoryInterface
{
    public function __construct(
        private DatabaseService $dbHelper,
        private string          $TABLE_NAME,
        private string          $DATE_FORMAT,
    ) {}

    /**
     * Maps database row to Quote entity.
     * @param array $row
     * @return Quote
     * @throws Exception
     */
    private function mapRow(array $row): Quote
    {
        return new Quote(
            (int)$row['id'],
            (string)$row['quote'],
            (string)$row['author'],
            new DateTimeImmutable($row['fetched_on'])
        );
    }

    /**
     * @inheritDoc
     */
    public function add(Quote $quote): int
    {
        return $this->dbHelper->insert(
            $this->TABLE_NAME,
            [
                'quote'         => [$quote->quote, PDO::PARAM_STR],
                'author'        => [$quote->author, PDO::PARAM_STR],
                'fetched_on'    => [$quote->fetchedOn->format($this->DATE_FORMAT), PDO::PARAM_STR],
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function fetchLatestQuote(): ?Quote
    {
        $date = date($this->DATE_FORMAT);
        $row = $this->dbHelper->getOne(
            "SELECT * FROM $this->TABLE_NAME WHERE fetched_on = :fetched_on",
            ['fetched_on' => [$date, PDO::PARAM_STR]]
        );

        return $row === null ? null : $this->mapRow($row);
    }

}