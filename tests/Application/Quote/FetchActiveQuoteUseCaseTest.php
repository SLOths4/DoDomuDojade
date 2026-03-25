<?php

declare(strict_types=1);

namespace App\Tests\Application\Quote;

use App\Application\Quote\FetchActiveQuoteUseCase;
use App\Domain\Quote\Quote;
use App\Domain\Quote\QuoteRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class FetchActiveQuoteUseCaseTest extends TestCase
{
    public function testExecuteReturnsLatestQuoteFromRepository(): void
    {
        $quote = new Quote(1, 'Test quote', 'Author', new DateTimeImmutable('2026-01-01 00:00:00'));

        $repository = $this->createMock(QuoteRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('fetchLatestQuote')
            ->willReturn($quote);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');

        $useCase = new FetchActiveQuoteUseCase($logger, $repository);

        self::assertSame($quote, $useCase->execute());
    }
}
