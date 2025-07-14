<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\FileDetector;

use MoveElevator\ComposerTranslationValidator\FileDetector\Collector;
use MoveElevator\ComposerTranslationValidator\FileDetector\DetectorInterface;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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

    public function testCollectFilesWithExcludedFiles(): void
    {
        file_put_contents($this->tempDir.'/test.xlf', 'content');
        file_put_contents($this->tempDir.'/excluded.xlf', 'content');

        $logger = $this->createMock(LoggerInterface::class);
        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturn(['mapped_data']);

        $collector = new Collector($logger);

        $result = $collector->collectFiles([$this->tempDir], $detector, ['excluded.xlf']);

        $this->assertArrayHasKey(XliffParser::class, $result);
        $this->assertArrayHasKey($this->tempDir, $result[XliffParser::class]);
        $this->assertEquals(['mapped_data'], $result[XliffParser::class][$this->tempDir]);
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

    public function testCollectFilesWithComplexExcludePattern(): void
    {
        file_put_contents($this->tempDir.'/keep.xlf', 'content');
        file_put_contents($this->tempDir.'/vendor_file.xlf', 'content');
        mkdir($this->tempDir.'/vendor');
        file_put_contents($this->tempDir.'/vendor/test.xlf', 'content');

        $logger = $this->createMock(LoggerInterface::class);
        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturn(['mapped_data']);

        $collector = new Collector($logger);

        $result = $collector->collectFiles([$this->tempDir], $detector, ['vendor*']);

        $this->assertArrayHasKey(XliffParser::class, $result);
        $this->assertArrayHasKey($this->tempDir, $result[XliffParser::class]);
        $this->assertEquals(['mapped_data'], $result[XliffParser::class][$this->tempDir]);
    }

    public function testCollectFilesWithMultiplePaths(): void
    {
        $tempDir2 = sys_get_temp_dir().'/collector_test2_'.uniqid('', true);
        mkdir($tempDir2);

        file_put_contents($this->tempDir.'/test1.xlf', 'content1');
        file_put_contents($tempDir2.'/test2.xlf', 'content2');

        $logger = $this->createMock(LoggerInterface::class);
        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturn(['mapped_data']);

        $collector = new Collector($logger);

        $result = $collector->collectFiles([$this->tempDir, $tempDir2], $detector, null);

        $this->assertArrayHasKey(XliffParser::class, $result);
        $this->assertArrayHasKey($this->tempDir, $result[XliffParser::class]);
        $this->assertArrayHasKey($tempDir2, $result[XliffParser::class]);

        // Clean up
        unlink($tempDir2.'/test2.xlf');
        rmdir($tempDir2);
    }
}
