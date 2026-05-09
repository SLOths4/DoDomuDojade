<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Word\Word;
use App\Domain\Word\WordRepositoryInterface;
use App\Infrastructure\Database\DatabaseService;
use DateTimeImmutable;
use Exception;
use PDO;

/**
 * @inheritDoc
 */
readonly class PDOWordRepository implements WordRepositoryInterface
{
    private const string TABLE_NAME = 'word';

    public function __construct(
        private DatabaseService $dbHelper,
        private string          $DATE_FORMAT,
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
     * @inheritDoc
     */
    public function add(Word $word): bool
    {
        $lastId = $this->dbHelper->insert(
            self::TABLE_NAME,
            [
                'word'              => [$word->word, PDO::PARAM_STR],
                'ipa'               => [$word->ipa, PDO::PARAM_STR],
                'definition'        => [$word->definition, PDO::PARAM_STR],
                'fetched_on'        => [$word->fetchedOn->format($this->DATE_FORMAT), PDO::PARAM_STR],
            ]
        );

        return !empty($lastId);
    }

    /**
     * @inheritDoc
     */
    public function fetchLatestWord(): ?Word
    {
        $date = date($this->DATE_FORMAT);
        $row = $this->dbHelper->getOne(
            "SELECT * FROM " . self::TABLE_NAME . " WHERE fetched_on = :fetched_on",
            ['fetched_on' => [$date, PDO::PARAM_STR]]
        );
        return $row === null ? null : $this->mapRow($row);
    }
}