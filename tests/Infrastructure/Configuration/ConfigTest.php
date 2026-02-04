<?php

namespace App\Tests\Infrastructure\Configuration;

use App\Infrastructure\Configuration\Config;
use App\Infrastructure\Configuration\ConfigException;
use App\Tests\Support\EnvHelper;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    use EnvHelper;

    #[After]
    public function tearDownEnv(): void
    {
        $this->restoreEnvVars();
    }

    public function testFromEnvReadsValues(): void
    {
        $this->setEnvVars([
            'LOGGING_DIRECTORY_PATH' => '/tmp/logs',
            'TWIG_CACHE_PATH' => '/tmp/twig',
            'IMGW_WEATHER_URL' => 'https://weather.test',
            'AIRLY_ENDPOINT' => 'https://airly.test',
            'AIRLY_API_KEY' => 'token',
            'TRAM_URL' => 'https://tram.test',
            'CALENDAR_API_KEY_PATH' => '/tmp/calendar.json',
            'CALENDAR_ID' => 'calendar-id',
            'QUOTE_API_URL' => 'https://quote.test',
            'WORD_API_URL' => 'https://word.test',
            'TWIG_DEBUG' => 'true',
            'STOP_ID' => '123, 456,',
        ]);

        $config = Config::fromEnv();

        self::assertSame('/tmp/logs', $config->loggingDirectoryPath);
        self::assertTrue($config->twigDebug);
        self::assertSame(['123', '456'], $config->stopID);
        self::assertSame('pgsql:host=localhost;port=5432;dbname=dodomudojade', $config->dbDsn());
    }

    public function testFromEnvThrowsWhenMissingRequired(): void
    {
        $this->setEnvVars([
            'LOGGING_DIRECTORY_PATH' => null,
            'TWIG_CACHE_PATH' => null,
            'IMGW_WEATHER_URL' => null,
            'AIRLY_ENDPOINT' => null,
            'AIRLY_API_KEY' => null,
            'TRAM_URL' => null,
            'CALENDAR_API_KEY_PATH' => null,
            'CALENDAR_ID' => null,
            'QUOTE_API_URL' => null,
            'WORD_API_URL' => null,
        ]);

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Missing required environment variable');

        Config::fromEnv();
    }
}
