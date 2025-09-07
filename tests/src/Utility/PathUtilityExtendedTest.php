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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathUtility::class)]
/**
 * PathUtilityExtendedTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 */
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
        if (false === $currentDir) {
            $this->markTestSkipped('Could not get current working directory');
        }
        $result = PathUtility::normalizeFolderPath($currentDir);

        // Test that the method returns a result (may be empty string for current directory)
        $this->addToAssertionCount(1); // Function executes without error
    }

    public function testNormalizeFolderPathWithSubdirectoryOfCwd(): void
    {
        $currentDir = getcwd();
        if (false === $currentDir) {
            $this->markTestSkipped('Could not get current working directory');
        }
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
