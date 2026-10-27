<?php

namespace App\Tests\Infrastructure\Helper;

use App\Domain\Countdown\CountdownBusinessValidator;
use App\Domain\Countdown\CountdownException;
use App\Infrastructure\Helper\CountdownValidationHelper;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CountdownValidationHelperTest extends TestCase
{
    private function createValidator(): CountdownBusinessValidator
    {
        return new CountdownBusinessValidator(
            minTitleLength: 3,
            maxTitleLength: 5
        );
    }

    public function testValidateTitleRejectsEmpty(): void
    {
        $helper = new CountdownValidationHelper($this->createValidator());

        $this->expectException(CountdownException::class);
        $helper->validateTitle('');
    }

    public function testValidateTitleRejectsTooShort(): void
    {
        $helper = new CountdownValidationHelper($this->createValidator());

        $this->expectException(CountdownException::class);
        $helper->validateTitle('ab');
    }

    public function testValidateCountToDateRejectsPast(): void
    {
        $helper = new CountdownValidationHelper($this->createValidator());

        $this->expectException(CountdownException::class);
        $helper->validateCountToDate(new DateTimeImmutable('-1 day'));
    }

    public function testValidateIdRejectsNonPositive(): void
    {
        $helper = new CountdownValidationHelper($this->createValidator());

        $this->expectException(CountdownException::class);
        $helper->validateId(0);
    }
}
