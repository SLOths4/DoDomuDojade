<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Word\Word;
use App\Infrastructure\Helper\DatabaseHelper;
use DateTimeImmutable;
use Exception;
use PDO;

readonly class WordRepository
{

    public function __construct(
        private DatabaseHelper $dbHelper,
        private string         $TABLE_NAME,
        private string         $DATE_FORMAT,
    ){}

    /**
     * Maps database row to Word entity.
     * @param array $row
     * @return Word
     * @throws Exception
     */
    private function mapRow(array $row): Word
    {
        return new Word(
            (int)$row['id'],
            (string)$row['word'],
            (string)$row['ipa'],
            (string)$row['definition'],
            new DateTimeImmutable($row['fetched_on'])
        );
    }

    /**
     * Adds word object to database
     * @param Word $word
     * @return bool
     * @throws Exception
     */
    public function add(Word $word): bool
    {
        $lastId = $this->dbHelper->insert(
            $this->TABLE_NAME,
            [
                'word'              => [$word->word, PDO::PARAM_STR],
                'ipa'               => [$word->ipa, PDO::PARAM_STR],
                'definition'        => [$word->definition, PDO::PARAM_STR],
                'fetched_on'         => [$word->fetchedOn->format($this->DATE_FORMAT), PDO::PARAM_STR],
            ]
        );

        return !empty($lastId);
    }

    /**
     * Fetches word for today
     * @return ?Word
     * @throws Exception
     */
    public function fetchLatestWord(): ?Word
    {
        $date = date($this->DATE_FORMAT);
        $row = $this->dbHelper->getOne(
            "SELECT * FROM $this->TABLE_NAME WHERE fetched_on = :fetched_on",
            ['fetched_on' => [$date, PDO::PARAM_STR]]
        );
        return $row === null ? null : $this->mapRow($row);
    }

}