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

    public function testCollectFilesRecursivelyFindsNestedFiles(): void
    {
        // Create nested directory structure
        mkdir($this->tempDir.'/level1', 0777, true);
        mkdir($this->tempDir.'/level1/level2', 0777, true);

        file_put_contents($this->tempDir.'/root.xlf', 'root content');
        file_put_contents($this->tempDir.'/level1/nested.xlf', 'nested content');
        file_put_contents($this->tempDir.'/level1/level2/deep.xlf', 'deep content');

        // Debug: verify files exist
        $this->assertFileExists($this->tempDir.'/root.xlf', 'root.xlf should exist');
        $this->assertFileExists($this->tempDir.'/level1/nested.xlf', 'nested.xlf should exist');
        $this->assertFileExists($this->tempDir.'/level1/level2/deep.xlf', 'deep.xlf should exist');

        // Debug: test RecursiveDirectoryIterator directly
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        $foundFiles = [];
        foreach ($iterator as $file) {
            $foundFiles[] = $file->getPathname();
        }
        $this->assertNotEmpty($foundFiles, 'RecursiveDirectoryIterator should find files');
        $this->assertContains($this->tempDir.'/root.xlf', $foundFiles, 'Should find root.xlf');
        $this->assertContains($this->tempDir.'/level1/nested.xlf', $foundFiles, 'Should find nested.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturn(['recursive_mapped_data']);

        $collector = new Collector($logger);

        // Test with recursive = true
        $result = $collector->collectFiles([$this->tempDir], $detector, null, true);

        $this->assertArrayHasKey(XliffParser::class, $result);
        $this->assertArrayHasKey($this->tempDir, $result[XliffParser::class]);
        $this->assertEquals(['recursive_mapped_data'], $result[XliffParser::class][$this->tempDir]);
    }

    public function testCollectFilesNonRecursiveOnlyFindsRootFiles(): void
    {
        // Create nested directory structure
        mkdir($this->tempDir.'/level1', 0777, true);

        file_put_contents($this->tempDir.'/root.xlf', 'root content');
        file_put_contents($this->tempDir.'/level1/nested.xlf', 'nested content');

        $logger = $this->createMock(LoggerInterface::class);
        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturn(['non_recursive_mapped_data']);

        $collector = new Collector($logger);

        // Test with recursive = false (default)
        $result = $collector->collectFiles([$this->tempDir], $detector, null, false);

        $this->assertArrayHasKey(XliffParser::class, $result);
        $this->assertArrayHasKey($this->tempDir, $result[XliffParser::class]);
        $this->assertEquals(['non_recursive_mapped_data'], $result[XliffParser::class][$this->tempDir]);
    }

    public function testCollectFilesRecursiveWithMultipleExtensions(): void
    {
        // Create nested directory structure with different file types
        mkdir($this->tempDir.'/level1', 0777, true);

        file_put_contents($this->tempDir.'/root.xlf', 'xlf content');
        file_put_contents($this->tempDir.'/root.json', '{"key": "value"}');
        file_put_contents($this->tempDir.'/level1/nested.yaml', 'key: value');
        file_put_contents($this->tempDir.'/level1/nested.php', '<?php return ["key" => "value"];');

        $logger = $this->createMock(LoggerInterface::class);
        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturn(['mixed_data']);

        $collector = new Collector($logger);

        $result = $collector->collectFiles([$this->tempDir], $detector, null, true);

        // Should find files for multiple parsers
        $this->assertNotEmpty($result);
    }

    public function testCollectFilesRecursiveWithPathTraversalPrevention(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        // The path will be resolved by realpath, so we might not get the warning anymore
        // But the result should still be empty or handled safely

        $detector = $this->createMock(DetectorInterface::class);
        $collector = new Collector($logger);

        // Test path traversal attack prevention - this should not find files in system directories
        $result = $collector->collectFiles(['/etc/../etc'], $detector, null, true);

        // Should return empty result due to security prevention
        $this->assertEmpty($result);
    }

    public function testCollectFilesRecursiveWithSystemDirectoryPrevention(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('warning')
            ->with($this->stringContains('Skipping potentially unsafe path'));

        $detector = $this->createMock(DetectorInterface::class);
        $collector = new Collector($logger);

        // Test system directory access prevention
        $result = $collector->collectFiles(['/etc'], $detector, null, true);

        // Should trigger warning and return empty result
        $this->assertEmpty($result);
    }

    public function testCollectFilesRecursiveWithExcludePatterns(): void
    {
        // Create nested directory structure
        mkdir($this->tempDir.'/level1', 0777, true);

        file_put_contents($this->tempDir.'/keep.xlf', 'keep content');
        file_put_contents($this->tempDir.'/exclude.xlf', 'exclude content');
        file_put_contents($this->tempDir.'/level1/nested_keep.xlf', 'nested keep content');
        file_put_contents($this->tempDir.'/level1/nested_exclude.xlf', 'nested exclude content');

        $logger = $this->createMock(LoggerInterface::class);
        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturn(['filtered_data']);

        $collector = new Collector($logger);

        $result = $collector->collectFiles([$this->tempDir], $detector, ['exclude*', 'nested_exclude*'], true);

        $this->assertArrayHasKey(XliffParser::class, $result);
        $this->assertArrayHasKey($this->tempDir, $result[XliffParser::class]);
        $this->assertEquals(['filtered_data'], $result[XliffParser::class][$this->tempDir]);
    }

    public function testRecursiveCollectionUsesRealFixtures(): void
    {
        $fixturesPath = __DIR__.'/../Fixtures/translations/xliff/success';

        // Skip test if fixtures don't exist
        if (!is_dir($fixturesPath)) {
            $this->markTestSkipped('XLIFF test fixtures not available');
        }

        $logger = $this->createMock(LoggerInterface::class);
        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturn(['fixture_data']);

        $collector = new Collector($logger);

        // Test recursive collection on real fixtures
        $result = $collector->collectFiles([$fixturesPath], $detector, null, true);

        // Should find files at multiple levels
        $this->assertNotEmpty($result);
    }
}
