<?php

namespace App\Tests\Infrastructure\Helper;

use App\Infrastructure\Helper\StaticFileHandlingHelper;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class StaticFileHandlingHelperTest extends TestCase
{
    private ?string $tempRoot = null;

    #[After]
    public function tearDownFiles(): void
    {
        if ($this->tempRoot === null) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tempRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
                continue;
            }

            unlink($file->getPathname());
        }

        rmdir($this->tempRoot);
        header_remove();
    }

    public function testServeReturnsFalseForDisallowedExtension(): void
    {
        $this->tempRoot = sys_get_temp_dir() . '/ddd_public_' . uniqid();
        mkdir($this->tempRoot, 0777, true);

        $helper = new StaticFileHandlingHelper($this->tempRoot);

        self::assertFalse($helper->serve('/index.php'));
    }

    public function testServeRejectsPathTraversal(): void
    {
        $base = sys_get_temp_dir() . '/ddd_public_' . uniqid();
        $this->tempRoot = $base . '/public';
        mkdir($this->tempRoot, 0777, true);

        $outsideFile = $base . '/secret.txt';
        file_put_contents($outsideFile, 'secret');

        $helper = new StaticFileHandlingHelper($this->tempRoot);

        self::assertFalse($helper->serve('/../secret.txt'));
    }

    public function testServeOutputsFileContents(): void
    {
        $this->tempRoot = sys_get_temp_dir() . '/ddd_public_' . uniqid();
        mkdir($this->tempRoot, 0777, true);

        $filePath = $this->tempRoot . '/styles.css';
        file_put_contents($filePath, 'body{color:red;}');

        $helper = new StaticFileHandlingHelper($this->tempRoot);

        ob_start();
        $served = $helper->serve('/styles.css');
        $output = ob_get_clean();

        self::assertTrue($served);
        self::assertSame('body{color:red;}', $output);
    }
}
