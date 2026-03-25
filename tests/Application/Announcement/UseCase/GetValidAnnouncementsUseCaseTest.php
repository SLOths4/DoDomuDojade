<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcement\UseCase;

use App\Application\Announcement\UseCase\GetValidAnnouncementsUseCase;
use App\Domain\Announcement\Announcement;
use App\Domain\Announcement\AnnouncementRepositoryInterface;
use App\Domain\Announcement\AnnouncementStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class GetValidAnnouncementsUseCaseTest extends TestCase
{
    public function testExecuteReturnsAnnouncementsFromRepository(): void
    {
        $announcements = [
            new Announcement(
                id: null,
                title: 'Title',
                text: 'Text',
                createdAt: new DateTimeImmutable('2026-01-01 00:00:00'),
                validUntil: new DateTimeImmutable('2026-12-31 00:00:00'),
                userId: 1,
                status: AnnouncementStatus::APPROVED,
            ),
        ];

        $repository = $this->createMock(AnnouncementRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findValid')
            ->willReturn($announcements);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('debug');

        $useCase = new GetValidAnnouncementsUseCase($repository, $logger);

        self::assertSame($announcements, $useCase->execute());
    }
}
