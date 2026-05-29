<?php

namespace App\Tests\Infrastructure\Helper;

use App\Domain\Announcement\AnnouncementBusinessValidator;
use App\Domain\Announcement\AnnouncementException;
use App\Domain\Announcement\AnnouncementId;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AnnouncementValidationHelperTest extends TestCase
{
    private function createValidator(string $maxValidDate = '+1 day'): AnnouncementBusinessValidator
    {
        return new AnnouncementBusinessValidator(
            minTitleLength: 3,
            maxTitleLength: 5,
            minTextLength: 4,
            maxTextLength: 8,
            maxValidDate: $maxValidDate
        );
    }

    public function testValidateTitleRequiresNonEmpty(): void
    {
        $helper = new AnnouncementValidationHelper($this->createValidator());

        $this->expectException(AnnouncementException::class);
        $helper->validateTitle('');
    }

    public function testValidateTitleChecksLength(): void
    {
        $helper = new AnnouncementValidationHelper($this->createValidator());

        $this->expectException(AnnouncementException::class);
        $helper->validateTitle('ab');
    }

    public function testValidateTitleAcceptsValidValue(): void
    {
        $helper = new AnnouncementValidationHelper($this->createValidator());

        $helper->validateTitle('abc');

        self::assertTrue(true);
    }

    public function testValidateTextChecksLength(): void
    {
        $helper = new AnnouncementValidationHelper($this->createValidator());

        $this->expectException(AnnouncementException::class);
        $helper->validateText('abc');
    }

    public function testValidateTextAcceptsValidValue(): void
    {
        $helper = new AnnouncementValidationHelper($this->createValidator());

        $helper->validateText('abcd');

        self::assertTrue(true);
    }

    public function testValidateValidUntilDateRejectsPastDate(): void
    {
        $helper = new AnnouncementValidationHelper($this->createValidator());

        $this->expectException(AnnouncementException::class);
        $helper->validateValidUntilDate(new DateTimeImmutable('-1 day'));
    }

    public function testValidateValidUntilDateRejectsFarFuture(): void
    {
        $helper = new AnnouncementValidationHelper($this->createValidator('+1 day'));

        $this->expectException(AnnouncementException::class);
        $helper->validateValidUntilDate(new DateTimeImmutable('+2 days'));
    }

    public function testValidateIdRequiresPrefix(): void
    {
        $helper = new AnnouncementValidationHelper($this->createValidator());

        $this->expectException(AnnouncementException::class);
        $helper->validateId(new AnnouncementId('bad-id'));
    }
}
