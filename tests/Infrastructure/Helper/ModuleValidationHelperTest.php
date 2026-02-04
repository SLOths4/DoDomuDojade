<?php

namespace App\Tests\Infrastructure\Helper;

use App\Domain\Module\ModuleException;
use App\Infrastructure\Helper\ModuleValidationHelper;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ModuleValidationHelperTest extends TestCase
{
    public function testValidateStartTimeNotGreaterThanEndTimeThrows(): void
    {
        $helper = new ModuleValidationHelper();
        $start = new DateTimeImmutable('10:00');
        $end = new DateTimeImmutable('09:00');

        $this->expectException(ModuleException::class);
        $helper->validateStartTimeNotGreaterThanEndTime($start, $end);
    }

    public function testValidateIdRejectsNonPositive(): void
    {
        $helper = new ModuleValidationHelper();

        $this->expectException(ModuleException::class);
        $helper->validateId(0);
    }
}
