<?php

namespace App\Tests\Presentation\Http\Context;

use App\Presentation\Http\Context\LocaleContext;
use PHPUnit\Framework\TestCase;

final class LocaleContextTest extends TestCase
{
    public function testSetAndGetLocale(): void
    {
        $context = new LocaleContext();

        $context->set('pl');

        self::assertSame('pl', $context->get());
    }
}
