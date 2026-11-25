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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * MismatchValidatorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class MismatchValidatorTest extends TestCase
{
    public function testProcessFile(): void
    {
        $parser1 = self::createStub(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser1->method('getFileName')->willReturn('file1.xlf');

        $parser2 = self::createStub(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['key2', 'key3']);
        $parser2->method('getFileName')->willReturn('file2.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $validator->processFile($parser1);
        $validator->processFile($parser2);

        // Accessing protected property for testing purposes
        $reflection = new ReflectionClass($validator);
        $keyArrayProperty = $reflection->getProperty('keyArray');
        $keyArray = $keyArrayProperty->getValue($validator);

        self::assertEquals(
            [
                'file1.xlf' => ['key1' => null, 'key2' => null],
                'file2.xlf' => ['key2' => null, 'key3' => null],
            ],
            $keyArray,
        );
    }

    public function testProcessFileWithEmptyFile(): void
    {
        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn([]);
        $parser->method('getFileName')->willReturn('empty.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);
        $result = $validator->processFile($parser);

        // Empty files should now be tracked with an empty array
        $reflection = new ReflectionClass($validator);
        $keyArrayProperty = $reflection->getProperty('keyArray');
        $keyArray = $keyArrayProperty->getValue($validator);

        self::assertArrayHasKey('empty.xlf', $keyArray);
        self::assertSame([], $keyArray['empty.xlf']);
        self::assertEmpty($result);
    }

    public function testPostProcessWithMismatches(): void
    {
        $parser1 = self::createStub(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser1->method('getFileName')->willReturn('file1.xlf');

        $parser2 = self::createStub(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['key2', 'key3']);
        $parser2->method('getFileName')->willReturn('file2.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $validator->processFile($parser1);
        $validator->processFile($parser2);

        $validator->postProcess();

        // Accessing protected property for testing purposes
        $reflection = new ReflectionClass($validator);
        $issuesProperty = $reflection->getProperty('issues');
        $issues = $issuesProperty->getValue($validator);

        $expectedIssues = [
            [
                'file' => '',
                'issues' => [
                    'key' => 'key1',
                    'files' => [
                        [
                            'file' => 'file1.xlf',
                            'value' => null,
                        ],
                        [
                            'file' => 'file2.xlf',
                            'value' => null,
                        ],
                    ],
                ],
                'parser' => '',
                'type' => 'MismatchValidator',
            ],
            [
                'file' => '',
                'issues' => [
                    'key' => 'key3',
                    'files' => [
                        [
                            'file' => 'file1.xlf',
                            'value' => null,
                        ],
                        [
                            'file' => 'file2.xlf',
                            'value' => null,
                        ],
                    ],
                ],
                'parser' => '',
                'type' => 'MismatchValidator',
            ],
        ];

        self::assertEquals($expectedIssues, array_map(static fn ($issue) => $issue->toArray(), $issues));
    }

    public function testPostProcessWithoutMismatches(): void
    {
        $parser1 = self::createStub(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser1->method('getFileName')->willReturn('file1.xlf');

        $parser2 = self::createStub(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser2->method('getFileName')->willReturn('file2.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $validator->processFile($parser1);
        $validator->processFile($parser2);

        $validator->postProcess();

        // Accessing protected property for testing purposes
        $reflection = new ReflectionClass($validator);
        $issuesProperty = $reflection->getProperty('issues');
        $issues = $issuesProperty->getValue($validator);

        self::assertEmpty($issues);
    }

    public function testResetStateResetsKeyArray(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        // Manually set keyArray to simulate previous validation
        $reflection = new ReflectionClass($validator);
        $keyArrayProperty = $reflection->getProperty('keyArray');
        $keyArrayProperty->setValue($validator, [
            'file1.xlf' => ['key1' => 'value1', 'key2' => 'value2'],
            'file2.xlf' => ['key1' => 'value1'],
        ]);

        // Verify keyArray is set
        self::assertNotEmpty($keyArrayProperty->getValue($validator));

        // Call resetState
        $resetStateMethod = $reflection->getMethod('resetState');
        $resetStateMethod->invoke($validator);

        // Verify keyArray is reset
        self::assertSame([], $keyArrayProperty->getValue($validator));

        // Verify issues are also reset (from parent)
        self::assertFalse($validator->hasIssues());
    }

    public function testDistributeIssuesForDisplay(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        // Create a mismatch issue
        $issue = new Issue(
            '',
            [
                'key' => 'test_key',
                'files' => [
                    ['file' => '/test/path/file1.xlf', 'value' => 'value1'],
                    ['file' => '/test/path/file2.xlf', 'value' => null],
                ],
            ],
            '',
            'MismatchValidator',
        );
        $validator->addIssue($issue);

        $fileSet = new FileSet(
            'TestParser',
            '/test/path',
            'setKey',
            ['file1.xlf', 'file2.xlf'],
        );

        $distribution = $validator->distributeIssuesForDisplay($fileSet);

        // MismatchValidator should create file-specific issues for each affected file
        self::assertArrayHasKey('/test/path/file1.xlf', $distribution);
        self::assertArrayHasKey('/test/path/file2.xlf', $distribution);
        self::assertCount(1, $distribution['/test/path/file1.xlf']);
        self::assertCount(1, $distribution['/test/path/file2.xlf']);
    }

    public function testShouldShowDetailedOutput(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        self::assertTrue($validator->shouldShowDetailedOutput());
    }

    public function testRenderDetailedOutput(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $output = new \Symfony\Component\Console\Output\BufferedOutput();

        // Create a test issue for detailed output
        $issue = new Issue(
            '',
            [
                'key' => 'test_key',
                'files' => [
                    ['file' => 'file1.xlf', 'value' => 'value1'],
                    ['file' => 'file2.xlf', 'value' => null],
                ],
            ],
            '',
            'MismatchValidator',
        );

        $validator->renderDetailedOutput($output, [$issue]);

        $outputContent = $output->fetch();

        // Should contain table output
        self::assertStringContainsString('Key', $outputContent);
        self::assertStringContainsString('file1.xlf', $outputContent);
        self::assertStringContainsString('file2.xlf', $outputContent);
        self::assertStringContainsString('test_key', $outputContent);
        // The table output shows empty cells for missing values, not "<missing>"
        self::assertStringContainsString('value1', $outputContent);
    }

    public function testFormatIssueMessage(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $issue = new Issue(
            'test.xlf',
            [
                'key' => 'test_key',
                'files' => [
                    ['file' => 'file1.xlf', 'value' => 'value1'],
                    ['file' => 'file2.xlf', 'value' => null],
                ],
            ],
            'TestParser',
            'MismatchValidator',
        );

        $result = $validator->formatIssueMessage($issue);

        self::assertStringContainsString('Error', $result);
        self::assertStringContainsString('test_key', $result);
        self::assertStringContainsString('files', $result);
        self::assertStringContainsString('<fg=red>', $result);
    }

    public function testFormatIssueMessageWhenCurrentFileHasValue(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        // The Issue file is file1.xlf and it carries a non-null value, so the
        // current file "has" the translation and it is "missing from" the others.
        $issue = new Issue(
            '/test/path/file1.xlf',
            [
                'key' => 'test_key',
                'files' => [
                    ['file' => '/test/path/file1.xlf', 'value' => 'value1'],
                    ['file' => '/test/path/file2.xlf', 'value' => null],
                ],
            ],
            'TestParser',
            'MismatchValidator',
        );

        $result = $validator->formatIssueMessage($issue);

        self::assertStringContainsString('is missing from other translation files', $result);
        self::assertStringContainsString('file2.xlf', $result);
        self::assertStringContainsString('test_key', $result);
    }

    public function testRenderDetailedOutputWithEmptyIssues(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $validator->renderDetailedOutput($output, []);

        self::assertSame('', $output->fetch());
    }

    public function testPostProcessDetectsEmptyFileWithMissingKeys(): void
    {
        $parser1 = self::createStub(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser1->method('getFileName')->willReturn('source.xlf');

        $parser2 = self::createStub(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn([]);
        $parser2->method('getFileName')->willReturn('empty.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $validator->processFile($parser1);
        $validator->processFile($parser2);

        $validator->postProcess();

        // Accessing protected property for testing purposes
        $reflection = new ReflectionClass($validator);
        $issuesProperty = $reflection->getProperty('issues');
        $issues = $issuesProperty->getValue($validator);

        // Should detect both keys as missing in the empty file
        self::assertCount(2, $issues);

        $issueArrays = array_map(static fn ($issue) => $issue->toArray(), $issues);

        // Check that both key1 and key2 are reported as mismatches
        $keys = array_map(static fn ($issue) => $issue['issues']['key'], $issueArrays);
        self::assertContains('key1', $keys);
        self::assertContains('key2', $keys);

        // Verify that the empty file is included in the details
        foreach ($issueArrays as $issueArray) {
            $files = $issueArray['issues']['files'];
            self::assertCount(2, $files);

            $fileNames = array_map(static fn ($file) => $file['file'], $files);
            self::assertContains('source.xlf', $fileNames);
            self::assertContains('empty.xlf', $fileNames);

            // Empty file should have null value for all keys
            foreach ($files as $file) {
                if ('empty.xlf' === $file['file']) {
                    self::assertNull($file['value']);
                }
            }
        }
    }

    public function testPostProcessWithMixedScenario(): void
    {
        // Source file with 6 keys
        $parser1 = self::createStub(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['key1', 'key2', 'key3', 'key4', 'key5', 'key6']);
        $parser1->method('getFileName')->willReturn('source.xlf');

        // Partial translation file with 3 keys
        $parser2 = self::createStub(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['key1', 'key2', 'key3']);
        $parser2->method('getFileName')->willReturn('partial.xlf');

        // Empty translation file
        $parser3 = self::createStub(ParserInterface::class);
        $parser3->method('extractKeys')->willReturn([]);
        $parser3->method('getFileName')->willReturn('empty.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $validator->processFile($parser1);
        $validator->processFile($parser2);
        $validator->processFile($parser3);

        $validator->postProcess();

        // Accessing protected property for testing purposes
        $reflection = new ReflectionClass($validator);
        $issuesProperty = $reflection->getProperty('issues');
        $issues = $issuesProperty->getValue($validator);

        // Should detect all 6 keys as mismatches since:
        // - key1, key2, key3 are missing from empty.xlf
        // - key4, key5, key6 are missing from both partial.xlf and empty.xlf
        self::assertCount(6, $issues);

        $issueArrays = array_map(static fn ($issue) => $issue->toArray(), $issues);
        $keys = array_map(static fn ($issue) => $issue['issues']['key'], $issueArrays);

        // All keys should be reported as missing somewhere
        self::assertContains('key1', $keys);
        self::assertContains('key2', $keys);
        self::assertContains('key3', $keys);
        self::assertContains('key4', $keys);
        self::assertContains('key5', $keys);
        self::assertContains('key6', $keys);

        // Verify that all three files are tracked in each issue
        foreach ($issueArrays as $issueArray) {
            $files = $issueArray['issues']['files'];
            self::assertCount(3, $files);
        }
    }

    public function testDistributeIssuesForDisplayWithNoIssues(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['file1.xlf', 'file2.xlf']);
        $distribution = $validator->distributeIssuesForDisplay($fileSet);

        self::assertSame([], $distribution);
    }

    public function testDistributeIssuesForDisplaySkipsEmptyFilePaths(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        // Create an issue where one file entry has an empty path
        $issue = new Issue(
            '',
            [
                'key' => 'test_key',
                'files' => [
                    ['file' => '/test/path/file1.xlf', 'value' => 'value1'],
                    ['file' => '', 'value' => null],
                ],
            ],
            '',
            'MismatchValidator',
        );
        $validator->addIssue($issue);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['file1.xlf']);
        $distribution = $validator->distributeIssuesForDisplay($fileSet);

        // Only the non-empty file path should appear in the distribution
        self::assertCount(1, $distribution);
        self::assertArrayHasKey('/test/path/file1.xlf', $distribution);
        self::assertArrayNotHasKey('', $distribution);
    }

    public function testDistributeIssuesForDisplayDistributesMultipleIssuesAcrossFiles(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $issue1 = new Issue(
            '',
            [
                'key' => 'key1',
                'files' => [
                    ['file' => '/test/path/en.xlf', 'value' => 'Hello'],
                    ['file' => '/test/path/de.xlf', 'value' => null],
                ],
            ],
            '',
            'MismatchValidator',
        );

        $issue2 = new Issue(
            '',
            [
                'key' => 'key2',
                'files' => [
                    ['file' => '/test/path/en.xlf', 'value' => null],
                    ['file' => '/test/path/de.xlf', 'value' => 'Welt'],
                ],
            ],
            '',
            'MismatchValidator',
        );

        $validator->addIssue($issue1);
        $validator->addIssue($issue2);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['en.xlf', 'de.xlf']);
        $distribution = $validator->distributeIssuesForDisplay($fileSet);

        // Both files should have 2 issues each
        self::assertCount(2, $distribution);
        self::assertCount(2, $distribution['/test/path/en.xlf']);
        self::assertCount(2, $distribution['/test/path/de.xlf']);

        // Verify the distributed issues have file-specific paths
        $enIssue = $distribution['/test/path/en.xlf'][0];
        self::assertSame('/test/path/en.xlf', $enIssue->getFile());

        $deIssue = $distribution['/test/path/de.xlf'][0];
        self::assertSame('/test/path/de.xlf', $deIssue->getFile());
    }
}
