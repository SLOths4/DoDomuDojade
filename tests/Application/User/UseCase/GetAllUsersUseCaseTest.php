<?php

declare(strict_types=1);

namespace App\Tests\Application\User\UseCase;

use App\Application\User\UseCase\GetAllUsersUseCase;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class GetAllUsersUseCaseTest extends TestCase
{
    public function testExecuteReturnsUsersFromRepository(): void
    {
        $users = [new User(1, 'john', 'hash', new DateTimeImmutable('2026-01-01 00:00:00'))];

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn($users);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('debug');

        $useCase = new GetAllUsersUseCase($repository, $logger);

        self::assertSame($users, $useCase->execute());
    }
}
