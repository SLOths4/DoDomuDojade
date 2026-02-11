<?php

namespace App\Tests\Infrastructure\Helper;

use App\Domain\Countdown\CountdownException;
use App\Infrastructure\Configuration\Config;
use App\Infrastructure\Helper\CountdownValidationHelper;
use App\Tests\Support\EnvHelper;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class CountdownValidationHelperTest extends TestCase
{
    use EnvHelper;

    #[After]
    public function tearDownEnv(): void
    {
        $this->restoreEnvVars();
    }

    private function createConfig(): Config
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
            'ANNOUNCEMENT_MIN_TITLE_LENGTH' => '3',
            'ANNOUNCEMENT_MAX_TITLE_LENGTH' => '5',
        ]);

        return Config::fromEnv();
    }

    public function testValidateTitleRejectsEmpty(): void
    {
        $helper = new CountdownValidationHelper($this->createConfig());

        $this->expectException(CountdownException::class);
        $helper->validateTitle('');
    }

    public function testValidateTitleRejectsTooShort(): void
    {
        $helper = new CountdownValidationHelper($this->createConfig());

        $this->expectException(CountdownException::class);
        $helper->validateTitle('ab');
    }

    public function testValidateCountToDateRejectsPast(): void
    {
        $helper = new CountdownValidationHelper($this->createConfig());

        $this->expectException(CountdownException::class);
        $helper->validateCountToDate(new DateTimeImmutable('-1 day'));
    }

    public function testValidateIdRejectsNonPositive(): void
    {
        $helper = new CountdownValidationHelper($this->createConfig());

        $this->expectException(CountdownException::class);
        $helper->validateId(0);
    }
}
