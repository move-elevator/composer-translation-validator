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

namespace MoveElevator\ComposerTranslationValidator\Tests\FileDetector;

use MoveElevator\ComposerTranslationValidator\FileDetector\Collector;
use MoveElevator\ComposerTranslationValidator\FileDetector\DetectorInterface;
use MoveElevator\ComposerTranslationValidator\Parser\JsonParser;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * CollectorTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 */
final class CollectorTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/collector_test_'.uniqid('', true);
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
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

    public function testCollectFilesWithValidPath(): void
    {
        file_put_contents($this->tempDir.'/test.xlf', 'content');

        $logger = $this->createMock(LoggerInterface::class);
        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturn(['mapped_data']);

        $collector = new Collector($logger);

        $result = $collector->collectFiles([$this->tempDir], $detector, null);

        $this->assertArrayHasKey(XliffParser::class, $result);
        $this->assertArrayHasKey($this->tempDir, $result[XliffParser::class]);
        $this->assertEquals(['mapped_data'], $result[XliffParser::class][$this->tempDir]);
    }

    public function testCollectFilesWithInvalidPath(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('The provided path'));

        $detector = $this->createMock(DetectorInterface::class);
        $collector = new Collector($logger);

        $result = $collector->collectFiles(['/non/existent/path'], $detector, null);

        $this->assertEmpty($result);
    }

    public function testCollectFilesWithNoMatchingFiles(): void
    {
        file_put_contents($this->tempDir.'/test.txt', 'content'); // Not an .xlf file

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(4))
            ->method('debug')
            ->with($this->stringContains('No files found for parser class'));

        $detector = $this->createMock(DetectorInterface::class);
        $collector = new Collector($logger);

        $result = $collector->collectFiles([$this->tempDir], $detector, null);

        $this->assertEmpty($result);
    }

    public function testCollectFilesWithEmptyGlobResult(): void
    {
        // Create a directory but no files matching the parser extensions
        mkdir($this->tempDir.'/subdir');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(4))
            ->method('debug')
            ->with($this->stringContains('No files found for parser class'));

        $detector = $this->createMock(DetectorInterface::class);
        $collector = new Collector($logger);

        $result = $collector->collectFiles([$this->tempDir], $detector, null);

        $this->assertEmpty($result);
    }

    public function testCollectFilesWithYamlFiles(): void
    {
        file_put_contents($this->tempDir.'/test.yaml', 'content');
        file_put_contents($this->tempDir.'/test.yml', 'content');

        $logger = $this->createMock(LoggerInterface::class);
        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturn(['mapped_yaml_data']);

        $collector = new Collector($logger);

        $result = $collector->collectFiles([$this->tempDir], $detector, null);

        $this->assertArrayHasKey(YamlParser::class, $result);
        $this->assertArrayHasKey($this->tempDir, $result[YamlParser::class]);
        $this->assertEquals(['mapped_yaml_data'], $result[YamlParser::class][$this->tempDir]);
    }

    public function testCollectFilesRecursive(): void
    {
        mkdir($this->tempDir.'/level1', 0777, true);
        file_put_contents($this->tempDir.'/root.xlf', 'root content');
        file_put_contents($this->tempDir.'/level1/nested.xlf', 'nested content');

        $logger = $this->createMock(LoggerInterface::class);
        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturn(['recursive_data']);

        $collector = new Collector($logger);
        $result = $collector->collectFiles([$this->tempDir], $detector, null, true);

        $this->assertArrayHasKey(XliffParser::class, $result);
        $this->assertEquals(['recursive_data'], $result[XliffParser::class][$this->tempDir]);
    }

    public function testCollectFilesWithMultipleFileTypes(): void
    {
        file_put_contents($this->tempDir.'/test.xlf', 'xlf content');
        file_put_contents($this->tempDir.'/test.json', '{"key": "value"}');
        file_put_contents($this->tempDir.'/test.yaml', 'key: value');

        $logger = $this->createMock(LoggerInterface::class);
        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturn(['mixed_data']);

        $collector = new Collector($logger);
        $result = $collector->collectFiles([$this->tempDir], $detector, null);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey(XliffParser::class, $result);
        $this->assertArrayHasKey(JsonParser::class, $result);
        $this->assertArrayHasKey(YamlParser::class, $result);
    }

    public function testCollectFilesWithExcludePatterns(): void
    {
        file_put_contents($this->tempDir.'/keep.xlf', 'keep content');
        file_put_contents($this->tempDir.'/exclude.xlf', 'exclude content');

        $logger = $this->createMock(LoggerInterface::class);
        $detector = $this->createMock(DetectorInterface::class);
        $detector->expects($this->once())
            ->method('mapTranslationSet')
            ->with($this->callback(fn ($files) => 1 === count($files) && in_array($this->tempDir.'/keep.xlf', $files)))
            ->willReturn(['filtered_data']);

        $collector = new Collector($logger);
        $result = $collector->collectFiles([$this->tempDir], $detector, ['exclude*']);

        $this->assertArrayHasKey(XliffParser::class, $result);
        $this->assertEquals(['filtered_data'], $result[XliffParser::class][$this->tempDir]);
    }

    public function testCollectFilesWithoutDetector(): void
    {
        file_put_contents($this->tempDir.'/test.xlf', 'xliff content');

        $logger = $this->createMock(LoggerInterface::class);
        $collector = new Collector($logger);

        $result = $collector->collectFiles([$this->tempDir], null, null, false);

        $this->assertNotEmpty($result);
    }

    public function testCollectFilesLogsDebugWhenNoFilesFound(): void
    {
        file_put_contents($this->tempDir.'/readme.txt', 'content');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(4))
            ->method('debug')
            ->with($this->stringContains('No files found for parser class'));

        $detector = $this->createMock(DetectorInterface::class);
        $collector = new Collector($logger);

        $result = $collector->collectFiles([$this->tempDir], $detector, null);

        $this->assertEmpty($result);
    }
}
