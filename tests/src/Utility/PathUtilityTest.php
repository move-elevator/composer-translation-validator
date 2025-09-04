<?php

declare(strict_types=1);

/*
 * This file is part of the Composer plugin "composer-translation-validator".
 *
 * Copyright (C) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Utility;

use MoveElevator\ComposerTranslationValidator\Utility\PathUtility;
use PHPUnit\Framework\TestCase;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

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
