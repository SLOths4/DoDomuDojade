<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Quote;
use App\Infrastructure\Helper\DatabaseHelper;
use DateTimeImmutable;
use Exception;
use PDO;

readonly class QuoteRepository
{
    public function __construct(
        private DatabaseHelper $dbHelper,
        private string         $TABLE_NAME,
        private string         $DATE_FORMAT,
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
     * Adds quote object to database
     * @param Quote $quote
     * @return bool
     * @throws Exception
     */
    public function add(Quote $quote): bool
    {
        $lastId = $this->dbHelper->insert(
            $this->TABLE_NAME,
            [
                'quote'         => [$quote->quote, PDO::PARAM_STR],
                'author'        => [$quote->author, PDO::PARAM_STR],
                'fetched_on'    => [$quote->fetchedOn->format($this->DATE_FORMAT), PDO::PARAM_STR],
            ]
        );

        return !empty($lastId);
    }

    /**
     * Fetches quote for today
     * @return ?Quote
     * @throws Exception
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