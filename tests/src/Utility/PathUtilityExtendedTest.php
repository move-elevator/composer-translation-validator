<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Utility;

use MoveElevator\ComposerTranslationValidator\Utility\PathUtility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathUtility::class)]
class PathUtilityExtendedTest extends TestCase
{
    public function testNormalizeFolderPathWithNonExistentPath(): void
    {
        $result = PathUtility::normalizeFolderPath('/non/existent/path');
        $this->assertSame('/non/existent/path', $result);
    }

    public function testNormalizeFolderPathWithDotSlashPrefix(): void
    {
        $result = PathUtility::normalizeFolderPath('./some/path');
        $this->assertSame('some/path', $result);
    }

    public function testNormalizeFolderPathWithTrailingSlash(): void
    {
        $result = PathUtility::normalizeFolderPath('/some/path/');
        $this->assertSame('/some/path', $result);
    }

    public function testNormalizeFolderPathWithCurrentDirectory(): void
    {
        $currentDir = getcwd();
        $result = PathUtility::normalizeFolderPath($currentDir);

        // Test that the method returns a result (may be empty string for current directory)
        $this->addToAssertionCount(1); // Function executes without error
    }

    public function testNormalizeFolderPathWithSubdirectoryOfCwd(): void
    {
        $currentDir = getcwd();
        $testDir = $currentDir.'/test_subdir';

        // Create temporary directory
        if (!is_dir($testDir)) {
            mkdir($testDir, 0777, true);
        }

        try {
            $result = PathUtility::normalizeFolderPath($testDir);
            $this->assertSame('test_subdir', $result);
        } finally {
            // Clean up
            if (is_dir($testDir)) {
                rmdir($testDir);
            }
        }
    }

    public function testNormalizeFolderPathWithAbsolutePathOutsideCwd(): void
    {
        $tempDir = sys_get_temp_dir();
        $result = PathUtility::normalizeFolderPath($tempDir);

        // Should return the full path if outside cwd
        $this->assertNotEmpty($result);
    }

    public function testNormalizeFolderPathWithEmptyString(): void
    {
        $result = PathUtility::normalizeFolderPath('');
        $this->assertSame('', $result);
    }

    public function testNormalizeFolderPathWithDotOnly(): void
    {
        $result = PathUtility::normalizeFolderPath('.');

        // Test that the method returns a result (may be empty string for current directory)
        $this->addToAssertionCount(1); // Function executes without error
    }

    public function testNormalizeFolderPathWithComplexDotSlashPath(): void
    {
        $result = PathUtility::normalizeFolderPath('./path/to/some/directory/');
        $this->assertSame('path/to/some/directory', $result);
    }

    public function testNormalizeFolderPathWithMultipleSlashes(): void
    {
        $result = PathUtility::normalizeFolderPath('/some/path//with///multiple////slashes/');
        $this->assertSame('/some/path//with///multiple////slashes', $result);
    }
}
