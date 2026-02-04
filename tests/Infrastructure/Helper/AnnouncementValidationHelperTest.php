<?php

namespace App\Tests\Infrastructure\Helper;

use App\Domain\Announcement\AnnouncementException;
use App\Domain\Announcement\AnnouncementId;
use App\Infrastructure\Configuration\Config;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Tests\Support\EnvHelper;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class AnnouncementValidationHelperTest extends TestCase
{
    use EnvHelper;

    #[After]
    public function tearDownEnv(): void
    {
        $this->restoreEnvVars();
    }

    private function createConfig(string $maxValidDate = '+1 day'): Config
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
            'ANNOUNCEMENT_MIN_TEXT_LENGTH' => '4',
            'ANNOUNCEMENT_MAX_TEXT_LENGTH' => '8',
            'ANNOUNCEMENT_MAX_VALID_DATE' => $maxValidDate,
        ]);

        return Config::fromEnv();
    }

    public function testValidateTitleRequiresNonEmpty(): void
    {
        $helper = new AnnouncementValidationHelper($this->createConfig());

        $this->expectException(AnnouncementException::class);
        $helper->validateTitle('');
    }

    public function testValidateTitleChecksLength(): void
    {
        $helper = new AnnouncementValidationHelper($this->createConfig());

        $this->expectException(AnnouncementException::class);
        $helper->validateTitle('ab');
    }

    public function testValidateTitleAcceptsValidValue(): void
    {
        $helper = new AnnouncementValidationHelper($this->createConfig());

        $helper->validateTitle('abc');

        self::assertTrue(true);
    }

    public function testValidateTextChecksLength(): void
    {
        $helper = new AnnouncementValidationHelper($this->createConfig());

        $this->expectException(AnnouncementException::class);
        $helper->validateText('abc');
    }

    public function testValidateTextAcceptsValidValue(): void
    {
        $helper = new AnnouncementValidationHelper($this->createConfig());

        $helper->validateText('abcd');

        self::assertTrue(true);
    }

    public function testValidateValidUntilDateRejectsPastDate(): void
    {
        $helper = new AnnouncementValidationHelper($this->createConfig());

        $this->expectException(AnnouncementException::class);
        $helper->validateValidUntilDate(new DateTimeImmutable('-1 day'));
    }

    public function testValidateValidUntilDateRejectsFarFuture(): void
    {
        $helper = new AnnouncementValidationHelper($this->createConfig('+1 day'));

        $this->expectException(AnnouncementException::class);
        $helper->validateValidUntilDate(new DateTimeImmutable('+2 days'));
    }

    public function testValidateIdRequiresPrefix(): void
    {
        $helper = new AnnouncementValidationHelper($this->createConfig());

        $this->expectException(AnnouncementException::class);
        $helper->validateId(new AnnouncementId('bad-id'));
    }
}
