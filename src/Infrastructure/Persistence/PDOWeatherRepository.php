<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Weather\WeatherRepositoryInterface;
use App\Domain\Weather\WeatherSnapshot;
use App\Infrastructure\Database\DatabaseService;
use DateTimeImmutable;
use Exception;
use PDO;

/**
 * @inheritDoc
 */
readonly class PDOWeatherRepository implements WeatherRepositoryInterface
{
    public function __construct(
        private DatabaseService $dbHelper,
        private string $TABLE_NAME,
        private string $DATE_FORMAT,
    ) {}

    /**
     * @param array<string, mixed> $row
     * @return WeatherSnapshot
     * @throws Exception
     */
    private function mapRow(array $row): WeatherSnapshot
    {
        $payload = json_decode((string)($row['payload'] ?? '[]'), true);

        if (!is_array($payload)) {
            $payload = [];
        }

        return new WeatherSnapshot(
            payload: $payload,
            fetchedOn: new DateTimeImmutable($row['fetched_on']),
            id: isset($row['id']) ? (int)$row['id'] : null
        );
    }

    /**
     * @inheritDoc
     */
    public function add(WeatherSnapshot $snapshot): int
    {
        $payload = json_encode($snapshot->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            $payload = '[]';
        }

        return $this->dbHelper->insert(
            $this->TABLE_NAME,
            [
                'payload' => [$payload, PDO::PARAM_STR],
                'fetched_on' => [$snapshot->fetchedOn->format($this->DATE_FORMAT), PDO::PARAM_STR],
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function fetchLatest(): ?WeatherSnapshot
    {
        $row = $this->dbHelper->getOne(
            "SELECT * FROM $this->TABLE_NAME ORDER BY fetched_on DESC LIMIT 1"
        );

        return $row === null ? null : $this->mapRow($row);
    }
}
