<?php

namespace App\Tests\Infrastructure\Translation;

use App\Infrastructure\Translation\LanguageTranslator;
use App\Presentation\Http\Context\LocaleContext;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class LanguageTranslatorTest extends TestCase
{
    private ?string $translationsDir = null;

    #[After]
    public function tearDownTranslations(): void
    {
        if ($this->translationsDir === null) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->translationsDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
                continue;
            }

            unlink($file->getPathname());
        }

        rmdir($this->translationsDir);
    }

    private function createTranslations(): string
    {
        $this->translationsDir = sys_get_temp_dir() . '/ddd_lang_' . uniqid();
        mkdir($this->translationsDir, 0777, true);

        file_put_contents(
            $this->translationsDir . '/en.php',
            "<?php\nreturn ['auth' => ['invalid_credentials' => 'Invalid :name'], 'simple' => 'ok'];\n"
        );
        file_put_contents(
            $this->translationsDir . '/pl.php',
            "<?php\nreturn ['auth' => ['invalid_credentials' => 'Błędne dane']];\n"
        );

        return $this->translationsDir;
    }

    public function testTranslateUsesLocaleAndParams(): void
    {
        $locale = new LocaleContext();
        $locale->set('en');

        $translator = new LanguageTranslator($locale, $this->createTranslations());

        self::assertSame('Invalid admin', $translator->translate('auth.invalid_credentials', ['name' => 'admin']));
    }

    public function testTranslateFallsBackToDefaultLocale(): void
    {
        $locale = new LocaleContext();
        $locale->set('pl');

        $translator = new LanguageTranslator($locale, $this->createTranslations());

        self::assertSame('ok', $translator->translate('simple'));
    }

    public function testTranslateReturnsKeyWhenMissing(): void
    {
        $locale = new LocaleContext();
        $locale->set('pl');

        $translator = new LanguageTranslator($locale, $this->createTranslations());

        self::assertSame('missing.key', $translator->translate('missing.key'));
    }
}
