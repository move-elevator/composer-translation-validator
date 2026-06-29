<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025-2026 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Utility;

use MoveElevator\ComposerTranslationValidator\Utility\PathUtility;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};
use PHPUnit\Framework\TestCase;

/**
 * PathUtilityTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
#[CoversClass(PathUtility::class)]
final class PathUtilityTest extends TestCase
{
    private string $originalCwd;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $cwd = getcwd();
        if (false === $cwd) {
            $this->fail('Could not get current working directory');
        }
        $this->originalCwd = $cwd;
        $this->tempDir = sys_get_temp_dir().'/path_utility_test_'.uniqid('', true);
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

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function normalizeFolderPathProvider(): iterable
    {
        yield 'absolute path' => ['/path/to/folder', '/path/to/folder'];
        yield 'dot-slash prefix' => ['./path/to/folder', 'path/to/folder'];
        yield 'empty path' => ['', ''];
        yield 'dot-slash prefix with trailing slash' => ['./path/to/folder/', 'path/to/folder'];
        yield 'only dot-slash' => ['./', ''];
        yield 'only dot' => ['.', ''];
        yield 'multiple trailing slashes' => ['/path/to/folder///', '/path/to/folder'];
        yield 'single slash' => ['/', ''];
        yield 'non-existent absolute path' => ['/non/existent/path', '/non/existent/path'];
        yield 'relative non-existent path' => ['relative/non/existent/path', 'relative/non/existent/path'];
        yield 'dots in path' => ['/path/../other/path', '/path/../other/path'];
        yield 'complex dot-slash path' => ['./path/to/some/directory/', 'path/to/some/directory'];
        yield 'multiple inner slashes preserved' => ['/some/path//with///multiple////slashes/', '/some/path//with///multiple////slashes'];
    }

    #[DataProvider('normalizeFolderPathProvider')]
    public function testNormalizeFolderPath(string $input, string $expected): void
    {
        $this->assertSame($expected, PathUtility::normalizeFolderPath($input));
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

    private function removeDirectory(string $path): void
    {
        $files = glob($path.'/*');
        if (false === $files) {
            rmdir($path);

            return;
        }

        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }
}
