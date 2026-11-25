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

namespace MoveElevator\ComposerTranslationValidator\Tests\FileDetector;

use MoveElevator\ComposerTranslationValidator\FileDetector\{Collector, DetectorInterface, DirectoryFileDetector, PrefixFileDetector, SuffixFileDetector};
use MoveElevator\ComposerTranslationValidator\Parser\{JsonParser, PhpParser, XliffParser, YamlParser};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

use function count;
use function dirname;
use function function_exists;
use function in_array;

/**
 * CollectorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
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

    public function testCollectFilesWithValidPath(): void
    {
        file_put_contents($this->tempDir.'/test.xlf', 'content');

        $logger = $this->createStub(LoggerInterface::class);
        $detector = $this->createStub(DetectorInterface::class);
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

        $detector = $this->createStub(DetectorInterface::class);
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

        $detector = $this->createStub(DetectorInterface::class);
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

        $detector = $this->createStub(DetectorInterface::class);
        $collector = new Collector($logger);

        $result = $collector->collectFiles([$this->tempDir], $detector, null);

        $this->assertEmpty($result);
    }

    public function testCollectFilesWithYamlFiles(): void
    {
        file_put_contents($this->tempDir.'/test.yaml', 'content');
        file_put_contents($this->tempDir.'/test.yml', 'content');

        $logger = $this->createStub(LoggerInterface::class);
        $detector = $this->createStub(DetectorInterface::class);
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

        $logger = $this->createStub(LoggerInterface::class);
        $detector = $this->createStub(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturn(['recursive_data']);

        $collector = new Collector($logger);
        $result = $collector->collectFiles([$this->tempDir], $detector, null, true);

        $root = (string) realpath($this->tempDir);
        $this->assertArrayHasKey(XliffParser::class, $result);
        $this->assertEquals(['recursive_data'], $result[XliffParser::class][$root]);
        $this->assertEquals(['recursive_data'], $result[XliffParser::class][$root.'/level1']);
    }

    public function testCollectFilesWithExplicitDetectorKeepsDirectoriesApartWhenRecursive(): void
    {
        mkdir($this->tempDir.'/a');
        mkdir($this->tempDir.'/b');
        foreach (['a', 'b'] as $directory) {
            file_put_contents($this->tempDir.'/'.$directory.'/messages.en.yaml', 'k: x');
            file_put_contents($this->tempDir.'/'.$directory.'/messages.de.yaml', 'k: x');
        }

        $collector = new Collector($this->createStub(LoggerInterface::class));
        $result = $collector->collectFiles([$this->tempDir], new SuffixFileDetector(), null, true);

        $sets = $result[YamlParser::class];
        ksort($sets);

        $root = (string) realpath($this->tempDir);
        $this->assertSame([$root.'/a', $root.'/b'], array_keys($sets));
        foreach ($sets as $directory => $set) {
            $this->assertCount(2, $set['messages']);
            foreach ($set['messages'] as $file) {
                $this->assertSame($directory, dirname($file));
            }
        }
    }

    public function testCollectFilesWithExplicitDetectorSkipsDirectoriesWithoutMatches(): void
    {
        mkdir($this->tempDir.'/a');
        mkdir($this->tempDir.'/b');
        file_put_contents($this->tempDir.'/a/messages.en.yaml', 'k: x');
        file_put_contents($this->tempDir.'/b/unrelated.yaml', 'k: x');

        $collector = new Collector($this->createStub(LoggerInterface::class));
        $result = $collector->collectFiles([$this->tempDir], new SuffixFileDetector(), null, true);

        $this->assertSame([(string) realpath($this->tempDir).'/a'], array_keys($result[YamlParser::class]));
    }

    public function testCollectFilesTriesConfiguredDetectorsInOrder(): void
    {
        file_put_contents($this->tempDir.'/messages.en.yaml', 'k: x');
        file_put_contents($this->tempDir.'/messages.de.yaml', 'k: x');

        $collector = new Collector($this->createStub(LoggerInterface::class));
        $result = $collector->collectFiles(
            [$this->tempDir],
            [new DirectoryFileDetector(), new SuffixFileDetector()],
            null,
        );

        $this->assertCount(2, $result[YamlParser::class][$this->tempDir]['messages']);
    }

    public function testCollectFilesUsesFirstConfiguredDetectorThatFindsFiles(): void
    {
        file_put_contents($this->tempDir.'/messages.en.yaml', 'k: x');
        file_put_contents($this->tempDir.'/messages.de.yaml', 'k: x');

        $second = $this->createMock(DetectorInterface::class);
        $second->expects($this->never())->method('mapTranslationSet');

        $collector = new Collector($this->createStub(LoggerInterface::class));
        $result = $collector->collectFiles([$this->tempDir], [new SuffixFileDetector(), $second], null);

        $this->assertCount(2, $result[YamlParser::class][$this->tempDir]['messages']);
    }

    public function testCollectFilesDoesNotDuplicateFilesForRepeatedPaths(): void
    {
        file_put_contents($this->tempDir.'/messages.en.yaml', 'k: x');
        file_put_contents($this->tempDir.'/messages.de.yaml', 'k: x');

        $collector = new Collector($this->createStub(LoggerInterface::class));
        $result = $collector->collectFiles(
            [$this->tempDir, $this->tempDir],
            [new SuffixFileDetector(), new PrefixFileDetector()],
            null,
        );

        $this->assertCount(2, $result[YamlParser::class][$this->tempDir]['messages']);
    }

    public function testCollectFilesFallsBackToLaterDetectorForDirectoriesTheFirstOneDoesNotMatch(): void
    {
        mkdir($this->tempDir.'/a');
        mkdir($this->tempDir.'/b');
        file_put_contents($this->tempDir.'/a/messages.en.yaml', 'k: x');
        file_put_contents($this->tempDir.'/a/messages.de.yaml', 'k: x');
        file_put_contents($this->tempDir.'/b/en.messages.yaml', 'k: x');
        file_put_contents($this->tempDir.'/b/de.messages.yaml', 'k: x');

        $collector = new Collector($this->createStub(LoggerInterface::class));
        $result = $collector->collectFiles(
            [$this->tempDir],
            [new SuffixFileDetector(), new PrefixFileDetector()],
            null,
            true,
        );

        $root = (string) realpath($this->tempDir);
        $sets = $result[YamlParser::class];
        ksort($sets);

        $this->assertSame([$root.'/a', $root.'/b'], array_keys($sets));
        $this->assertCount(2, $sets[$root.'/a']['messages']);
        $this->assertCount(2, $sets[$root.'/b']['messages.yaml']);
    }

    public function testCollectFilesWithDirectoryDetectorStillSpansLanguageDirectories(): void
    {
        mkdir($this->tempDir.'/en');
        mkdir($this->tempDir.'/de');
        file_put_contents($this->tempDir.'/en/messages.yaml', 'k: x');
        file_put_contents($this->tempDir.'/de/messages.yaml', 'k: x');

        $collector = new Collector($this->createStub(LoggerInterface::class));
        $result = $collector->collectFiles([$this->tempDir], new DirectoryFileDetector(), null, true);

        $this->assertSame([$this->tempDir], array_keys($result[YamlParser::class]));
        $this->assertCount(2, $result[YamlParser::class][$this->tempDir]['messages']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function directoryExcludePatternProvider(): iterable
    {
        yield 'glob on the full path' => ['**/backup/**'];
        yield 'relative to the scanned path' => ['backup/*'];
        yield 'directory name' => ['backup'];
    }

    #[DataProvider('directoryExcludePatternProvider')]
    public function testCollectFilesExcludesFilesByDirectoryPattern(string $pattern): void
    {
        mkdir($this->tempDir.'/backup');
        mkdir($this->tempDir.'/keep');
        file_put_contents($this->tempDir.'/backup/app.de.yaml', 'k: x');
        file_put_contents($this->tempDir.'/keep/app.de.yaml', 'k: x');

        $collected = $this->collectedFileNames([$pattern]);

        $this->assertSame(['keep/app.de.yaml'], $collected);
    }

    public function testCollectFilesExcludesNestedDirectoriesByName(): void
    {
        mkdir($this->tempDir.'/nested/backup', 0777, true);
        file_put_contents($this->tempDir.'/nested/backup/app.de.yaml', 'k: x');
        file_put_contents($this->tempDir.'/nested/app.de.yaml', 'k: x');

        $this->assertSame(['nested/app.de.yaml'], $this->collectedFileNames(['backup']));
    }

    public function testCollectFilesIgnoresDirectoriesAboveTheScannedPathWhenMatching(): void
    {
        mkdir($this->tempDir.'/backup/project', 0777, true);
        file_put_contents($this->tempDir.'/backup/project/app.de.yaml', 'k: x');

        $collected = $this->collectedFileNames(['**/backup/**', '*backup*'], '/backup/project');

        $this->assertSame(['app.de.yaml'], $collected);
    }

    public function testCollectFilesDoesNotTraverseExcludedDirectories(): void
    {
        if (function_exists('posix_geteuid') && 0 === posix_geteuid()) {
            $this->markTestSkipped('Directory permissions are not enforced for root.');
        }

        mkdir($this->tempDir.'/vendor');
        file_put_contents($this->tempDir.'/app.de.yaml', 'k: x');
        chmod($this->tempDir.'/vendor', 0000);

        try {
            $collected = $this->collectedFileNames(['**/vendor/**']);
        } finally {
            chmod($this->tempDir.'/vendor', 0755);
        }

        $this->assertSame(['app.de.yaml'], $collected);
    }

    public function testCollectFilesAutoDetectsDirectoryBasedLayout(): void
    {
        mkdir($this->tempDir.'/en');
        mkdir($this->tempDir.'/de');
        file_put_contents($this->tempDir.'/en/messages.php', "<?php return ['a' => 'x'];");
        file_put_contents($this->tempDir.'/de/messages.php', "<?php return ['a' => 'x'];");

        $collector = new Collector($this->createStub(LoggerInterface::class));
        $result = $collector->collectFiles([$this->tempDir], null, null, true);

        $sets = array_values($result[PhpParser::class]);

        $this->assertCount(1, $sets);
        $this->assertCount(2, $sets[0]['messages']);
    }

    public function testCollectFilesAutoDetectsDirectoryLayoutsOfDifferentParentsSeparately(): void
    {
        foreach (['a', 'b'] as $parent) {
            mkdir($this->tempDir.'/'.$parent.'/en', 0777, true);
            mkdir($this->tempDir.'/'.$parent.'/de', 0777, true);
            file_put_contents($this->tempDir.'/'.$parent.'/en/messages.php', "<?php return ['a' => 'x'];");
            file_put_contents($this->tempDir.'/'.$parent.'/de/messages.php', "<?php return ['a' => 'x'];");
        }

        $collector = new Collector($this->createStub(LoggerInterface::class));
        $result = $collector->collectFiles([$this->tempDir], null, null, true);

        $sets = array_values($result[PhpParser::class]);

        $this->assertCount(2, $sets);
        foreach ($sets as $set) {
            $this->assertCount(2, $set['messages']);
        }
    }

    public function testCollectFilesKeepsDirectoryLayoutAndSuffixLayoutOfTheSameParent(): void
    {
        mkdir($this->tempDir.'/en');
        mkdir($this->tempDir.'/de');
        file_put_contents($this->tempDir.'/en/auth.php', "<?php return ['a' => 'x'];");
        file_put_contents($this->tempDir.'/de/auth.php', "<?php return ['a' => 'x'];");
        file_put_contents($this->tempDir.'/app.en.php', "<?php return ['a' => 'x'];");
        file_put_contents($this->tempDir.'/app.de.php', "<?php return ['a' => 'x'];");

        $collector = new Collector($this->createStub(LoggerInterface::class));
        $result = $collector->collectFiles([$this->tempDir], null, null, true);

        $setNames = [];
        foreach ($result[PhpParser::class] as $sets) {
            foreach ($sets as $name => $files) {
                $this->assertCount(2, $files);
                $setNames[] = $name;
            }
        }
        sort($setNames);

        $this->assertSame(['app', 'auth'], $setNames);
    }

    public function testCollectFilesKeepsLanguageNamedFilesInTwoLetterDirectoriesTogether(): void
    {
        mkdir($this->tempDir.'/ui');
        file_put_contents($this->tempDir.'/ui/en.json', '{"a": "x"}');
        file_put_contents($this->tempDir.'/ui/de.json', '{"a": "x"}');

        $collector = new Collector($this->createStub(LoggerInterface::class));
        $result = $collector->collectFiles([$this->tempDir], null, null, true);

        $sets = array_values($result[JsonParser::class]);

        $this->assertCount(1, $sets);
        $this->assertSame(['json'], array_keys($sets[0]));
        $this->assertCount(2, $sets[0]['json']);
    }

    public function testCollectFilesDoesNotFollowSymlinks(): void
    {
        if (!function_exists('symlink')) {
            $this->markTestSkipped('symlink() is not available on this platform.');
        }

        // Target directory lives outside the scanned tree and is only reachable
        // through the symlink created below.
        $outside = sys_get_temp_dir().'/collector_outside_'.uniqid('', true);
        mkdir($outside);
        file_put_contents($outside.'/secret.xlf', 'secret content');

        file_put_contents($this->tempDir.'/real.xlf', 'real content');
        if (!@symlink($outside, $this->tempDir.'/link')) {
            unlink($outside.'/secret.xlf');
            rmdir($outside);
            $this->markTestSkipped('Unable to create a symlink on this platform.');
        }

        $captured = [];
        $detector = $this->createStub(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturnCallback(
            static function (array $files) use (&$captured): array {
                $captured = array_merge($captured, $files);

                return ['data'];
            },
        );

        try {
            $collector = new Collector($this->createStub(LoggerInterface::class));
            $collector->collectFiles([$this->tempDir], $detector, null, true);

            $joined = implode('|', $captured);
            $this->assertStringContainsString('real.xlf', $joined);
            $this->assertStringNotContainsString('secret.xlf', $joined);
        } finally {
            unlink($this->tempDir.'/link');
            unlink($outside.'/secret.xlf');
            rmdir($outside);
        }
    }

    public function testCollectFilesWithMultipleFileTypes(): void
    {
        file_put_contents($this->tempDir.'/test.xlf', 'xlf content');
        file_put_contents($this->tempDir.'/test.json', '{"key": "value"}');
        file_put_contents($this->tempDir.'/test.yaml', 'key: value');

        $logger = $this->createStub(LoggerInterface::class);
        $detector = $this->createStub(DetectorInterface::class);
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

        $logger = $this->createStub(LoggerInterface::class);
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

        $logger = $this->createStub(LoggerInterface::class);
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

        $detector = $this->createStub(DetectorInterface::class);
        $collector = new Collector($logger);

        $result = $collector->collectFiles([$this->tempDir], $detector, null);

        $this->assertEmpty($result);
    }

    public function testCollectFilesWhenExcludePatternsFilterAllFiles(): void
    {
        file_put_contents($this->tempDir.'/messages.xlf', 'content');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('debug')
            ->with($this->stringContains('No files found for parser class'));

        $detector = $this->createStub(DetectorInterface::class);
        $collector = new Collector($logger);

        $result = $collector->collectFiles([$this->tempDir], $detector, ['*']);

        $this->assertEmpty($result);
    }

    public function testCollectFilesSkipsUnsafePathOnRecursiveSearch(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('warning')
            ->with($this->stringContains('Skipping potentially unsafe path'));

        $detector = $this->createStub(DetectorInterface::class);
        $collector = new Collector($logger);

        $result = $collector->collectFiles(['/etc'], $detector, null, true);

        $this->assertSame([], $result);
    }

    public function testCollectFilesSkipsOversizedFiles(): void
    {
        file_put_contents($this->tempDir.'/small.xlf', 'content');

        // Create a sparse file larger than the 30 MB limit without actually
        // writing 30 MB of data.
        $big = $this->tempDir.'/big.xlf';
        $handle = fopen($big, 'w');
        if (false === $handle) {
            $this->markTestSkipped('Unable to create the oversized test file.');
        }
        fseek($handle, 30 * 1024 * 1024);
        fwrite($handle, 'x');
        fclose($handle);

        $captured = [];
        $detector = $this->createStub(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturnCallback(
            static function (array $files) use (&$captured): array {
                $captured = array_merge($captured, $files);

                return ['data'];
            },
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('warning')
            ->with($this->stringContains('exceeding the maximum size'));

        $collector = new Collector($logger);
        $collector->collectFiles([$this->tempDir], $detector, null);

        $joined = implode('|', $captured);
        $this->assertStringContainsString('small.xlf', $joined);
        $this->assertStringNotContainsString('big.xlf', $joined);
    }

    public function testCollectFilesSkipsAdditionalSystemPaths(): void
    {
        if (!is_dir('/dev')) {
            $this->markTestSkipped('/dev is not available on this platform.');
        }

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('warning')
            ->with($this->stringContains('Skipping potentially unsafe path'));

        $detector = $this->createStub(DetectorInterface::class);
        $collector = new Collector($logger);

        $result = $collector->collectFiles(['/dev'], $detector, null, true);

        $this->assertSame([], $result);
    }

    /**
     * @param string[] $excludePatterns
     *
     * @return string[] collected files relative to the scanned directory
     */
    private function collectedFileNames(array $excludePatterns, string $subDirectory = ''): array
    {
        $collected = [];
        $detector = $this->createStub(DetectorInterface::class);
        $detector->method('mapTranslationSet')->willReturnCallback(
            static function (array $files) use (&$collected): array {
                $collected = array_merge($collected, $files);

                return ['set' => $files];
            },
        );

        (new Collector($this->createStub(LoggerInterface::class)))
            ->collectFiles([$this->tempDir.$subDirectory], $detector, $excludePatterns, true);

        $root = (string) realpath($this->tempDir.$subDirectory).'/';
        $relative = array_map(static fn (string $file): string => str_replace($root, '', $file), $collected);
        sort($relative);

        return $relative;
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
