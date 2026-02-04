<?php

namespace App\Tests\Infrastructure\Helper;

use App\Infrastructure\Helper\SessionHelper;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[PreserveGlobalState(false)]
final class SessionHelperTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testSetGetAndRemoveSessionData(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        SessionHelper::set('key', 'value');
        self::assertTrue(SessionHelper::has('key'));
        self::assertSame('value', SessionHelper::get('key'));

        SessionHelper::remove('key');
        self::assertFalse(SessionHelper::has('key'));
    }

    #[RunInSeparateProcess]
    public function testFingerprintValidation(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        SessionHelper::setWithFingerprint('user', 'alice');
        self::assertTrue(SessionHelper::validateFingerprint());

        $_SERVER['REMOTE_ADDR'] = '127.0.0.2';
        self::assertFalse(SessionHelper::validateFingerprint());
    }
}
