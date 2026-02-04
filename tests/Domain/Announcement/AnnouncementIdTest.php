<?php

namespace App\Tests\Domain\Announcement;

use App\Domain\Announcement\AnnouncementId;
use PHPUnit\Framework\TestCase;

final class AnnouncementIdTest extends TestCase
{
    public function testGenerateUsesPrefix(): void
    {
        $id = AnnouncementId::generate();

        self::assertStringStartsWith('ann_', $id->getValue());
    }

    public function testToStringMatchesValue(): void
    {
        $id = new AnnouncementId('ann_123');

        self::assertSame('ann_123', (string)$id);
    }
}
