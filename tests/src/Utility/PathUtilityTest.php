<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Utility;

use MoveElevator\ComposerTranslationValidator\Utility\PathUtility;
use PHPUnit\Framework\TestCase;

final class PathUtilityTest extends TestCase
{
    private string $originalCwd;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalCwd = getcwd();
        $this->tempDir = sys_get_temp_dir().'/path_utility_test_'.uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
        chdir($this->originalCwd);
    }

    private function removeDirectory(string $path): void
    {
        $files = glob($path.'/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }

    public function testNormalizeFolderPathWithTrailingSlash(): void
    {
        $path = '/path/to/folder';
        $this->assertSame('/path/to/folder', PathUtility::normalizeFolderPath($path));
    }

    public function testNormalizeFolderPathWithoutTrailingSlash(): void
    {
        $path = '/path/to/folder';
        $this->assertSame('/path/to/folder', PathUtility::normalizeFolderPath($path));
    }

    public function testNormalizeFolderPathWithDotSlashPrefix(): void
    {
        $path = './path/to/folder';
        $this->assertSame('path/to/folder', PathUtility::normalizeFolderPath($path));
    }

    public function testNormalizeFolderPathWithEmptyPath(): void
    {
        $path = '';
        $this->assertSame('', PathUtility::normalizeFolderPath($path));
    }

    public function testNormalizeFolderPathWhenPathIsCwd(): void
    {
        chdir($this->tempDir);
        $this->assertSame('', PathUtility::normalizeFolderPath($this->tempDir));
    }

    public function testNormalizeFolderPathWhenPathIsSubdirectoryOfCwd(): void
    {
        $subDir = $this->tempDir.'/sub';
        mkdir($subDir);
        chdir($this->tempDir);

        $this->assertSame('sub', PathUtility::normalizeFolderPath($subDir));
    }

    public function testNormalizeFolderPathWhenPathIsNotRelatedToCwd(): void
    {
        chdir($this->tempDir);
        $unrelatedPath = '/another/path/to/folder';
        $this->assertSame('/another/path/to/folder', PathUtility::normalizeFolderPath($unrelatedPath));
    }

    public function testNormalizeFolderPathWithDotSlashPrefixAndTrailingSlash(): void
    {
        $path = './path/to/folder/';
        $this->assertSame('path/to/folder', PathUtility::normalizeFolderPath($path));
    }

    public function testNormalizeFolderPathWithOnlyDotSlash(): void
    {
        $path = './';
        $this->assertSame('', PathUtility::normalizeFolderPath($path));
    }

    public function testNormalizeFolderPathWithOnlyDot(): void
    {
        $path = '.';
        $this->assertSame('', PathUtility::normalizeFolderPath($path));
    }

    public function testNormalizeFolderPathWithMultipleTrailingSlashes(): void
    {
        $path = '/path/to/folder///';
        $this->assertSame('/path/to/folder', PathUtility::normalizeFolderPath($path));
    }

    public function testNormalizeFolderPathWithSingleSlash(): void
    {
        $path = '/';
        $this->assertSame('', PathUtility::normalizeFolderPath($path));
    }

    public function testNormalizeFolderPathWithNonExistentPath(): void
    {
        $path = '/non/existent/path';
        $this->assertSame('/non/existent/path', PathUtility::normalizeFolderPath($path));
    }

    public function testNormalizeFolderPathWithRelativeNonExistentPath(): void
    {
        $path = 'relative/non/existent/path';
        $this->assertSame('relative/non/existent/path', PathUtility::normalizeFolderPath($path));
    }

    public function testNormalizeFolderPathWithDotsInPath(): void
    {
        $path = '/path/../other/path';
        $this->assertSame('/path/../other/path', PathUtility::normalizeFolderPath($path));
    }
}
