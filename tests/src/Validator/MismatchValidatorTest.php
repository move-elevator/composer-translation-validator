<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
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
        $parser1 = $this->createMock(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser1->method('getFileName')->willReturn('file1.xlf');

        $parser2 = $this->createMock(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['key2', 'key3']);
        $parser2->method('getFileName')->willReturn('file2.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $validator->processFile($parser1);
        $validator->processFile($parser2);

        // Accessing protected property for testing purposes
        $reflection = new ReflectionClass($validator);
        $keyArrayProperty = $reflection->getProperty('keyArray');
        $keyArray = $keyArrayProperty->getValue($validator);

        $this->assertEquals(
            [
                'file1.xlf' => ['key1' => null, 'key2' => null],
                'file2.xlf' => ['key2' => null, 'key3' => null],
            ],
            $keyArray,
        );
    }

    public function testProcessFileWithEmptyFile(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn([]);
        $parser->method('getFileName')->willReturn('empty.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);
        $result = $validator->processFile($parser);

        // Empty files should now be tracked with an empty array
        $reflection = new ReflectionClass($validator);
        $keyArrayProperty = $reflection->getProperty('keyArray');
        $keyArray = $keyArrayProperty->getValue($validator);

        $this->assertArrayHasKey('empty.xlf', $keyArray);
        $this->assertSame([], $keyArray['empty.xlf']);
        $this->assertEmpty($result);
    }

    public function testPostProcessWithMismatches(): void
    {
        $parser1 = $this->createMock(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser1->method('getFileName')->willReturn('file1.xlf');

        $parser2 = $this->createMock(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['key2', 'key3']);
        $parser2->method('getFileName')->willReturn('file2.xlf');

        $logger = $this->createMock(LoggerInterface::class);
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

        $this->assertEquals($expectedIssues, array_map(fn ($issue) => $issue->toArray(), $issues));
    }

    public function testPostProcessWithoutMismatches(): void
    {
        $parser1 = $this->createMock(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser1->method('getFileName')->willReturn('file1.xlf');

        $parser2 = $this->createMock(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser2->method('getFileName')->willReturn('file2.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $validator->processFile($parser1);
        $validator->processFile($parser2);

        $validator->postProcess();

        // Accessing protected property for testing purposes
        $reflection = new ReflectionClass($validator);
        $issuesProperty = $reflection->getProperty('issues');
        $issues = $issuesProperty->getValue($validator);

        $this->assertEmpty($issues);
    }

    public function testResetStateResetsKeyArray(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        // Manually set keyArray to simulate previous validation
        $reflection = new ReflectionClass($validator);
        $keyArrayProperty = $reflection->getProperty('keyArray');
        $keyArrayProperty->setValue($validator, [
            'file1.xlf' => ['key1' => 'value1', 'key2' => 'value2'],
            'file2.xlf' => ['key1' => 'value1'],
        ]);

        // Verify keyArray is set
        $this->assertNotEmpty($keyArrayProperty->getValue($validator));

        // Call resetState
        $resetStateMethod = $reflection->getMethod('resetState');
        $resetStateMethod->invoke($validator);

        // Verify keyArray is reset
        $this->assertSame([], $keyArrayProperty->getValue($validator));

        // Verify issues are also reset (from parent)
        $this->assertFalse($validator->hasIssues());
    }

    public function testSupportsParser(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $this->assertSame([
            \MoveElevator\ComposerTranslationValidator\Parser\XliffParser::class,
            \MoveElevator\ComposerTranslationValidator\Parser\YamlParser::class,
            \MoveElevator\ComposerTranslationValidator\Parser\JsonParser::class,
            \MoveElevator\ComposerTranslationValidator\Parser\PhpParser::class,
        ], $validator->supportsParser());
    }

    public function testDistributeIssuesForDisplay(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        // Create a mismatch issue
        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
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

        $fileSet = new \MoveElevator\ComposerTranslationValidator\FileDetector\FileSet(
            'TestParser',
            '/test/path',
            'setKey',
            ['file1.xlf', 'file2.xlf'],
        );

        $distribution = $validator->distributeIssuesForDisplay($fileSet);

        // MismatchValidator should create file-specific issues for each affected file
        $this->assertArrayHasKey('/test/path/file1.xlf', $distribution);
        $this->assertArrayHasKey('/test/path/file2.xlf', $distribution);
        $this->assertCount(1, $distribution['/test/path/file1.xlf']);
        $this->assertCount(1, $distribution['/test/path/file2.xlf']);
    }

    public function testShouldShowDetailedOutput(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $this->assertTrue($validator->shouldShowDetailedOutput());
    }

    public function testRenderDetailedOutput(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $output = new \Symfony\Component\Console\Output\BufferedOutput();

        // Create a test issue for detailed output
        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
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
        $this->assertStringContainsString('Key', $outputContent);
        $this->assertStringContainsString('file1.xlf', $outputContent);
        $this->assertStringContainsString('file2.xlf', $outputContent);
        $this->assertStringContainsString('test_key', $outputContent);
        // The table output shows empty cells for missing values, not "<missing>"
        $this->assertStringContainsString('value1', $outputContent);
    }

    public function testFormatIssueMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
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

        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('test_key', $result);
        $this->assertStringContainsString('files', $result);
        $this->assertStringContainsString('<fg=red>', $result);
    }

    public function testGetShortName(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $this->assertSame('MismatchValidator', $validator->getShortName());
    }

    public function testPostProcessDetectsEmptyFileWithMissingKeys(): void
    {
        $parser1 = $this->createMock(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser1->method('getFileName')->willReturn('source.xlf');

        $parser2 = $this->createMock(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn([]);
        $parser2->method('getFileName')->willReturn('empty.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $validator->processFile($parser1);
        $validator->processFile($parser2);

        $validator->postProcess();

        // Accessing protected property for testing purposes
        $reflection = new ReflectionClass($validator);
        $issuesProperty = $reflection->getProperty('issues');
        $issues = $issuesProperty->getValue($validator);

        // Should detect both keys as missing in the empty file
        $this->assertCount(2, $issues);

        $issueArrays = array_map(fn ($issue) => $issue->toArray(), $issues);

        // Check that both key1 and key2 are reported as mismatches
        $keys = array_map(fn ($issue) => $issue['issues']['key'], $issueArrays);
        $this->assertContains('key1', $keys);
        $this->assertContains('key2', $keys);

        // Verify that the empty file is included in the details
        foreach ($issueArrays as $issueArray) {
            $files = $issueArray['issues']['files'];
            $this->assertCount(2, $files);

            $fileNames = array_map(fn ($file) => $file['file'], $files);
            $this->assertContains('source.xlf', $fileNames);
            $this->assertContains('empty.xlf', $fileNames);

            // Empty file should have null value for all keys
            foreach ($files as $file) {
                if ('empty.xlf' === $file['file']) {
                    $this->assertNull($file['value']);
                }
            }
        }
    }

    public function testPostProcessWithMixedScenario(): void
    {
        // Source file with 6 keys
        $parser1 = $this->createMock(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['key1', 'key2', 'key3', 'key4', 'key5', 'key6']);
        $parser1->method('getFileName')->willReturn('source.xlf');

        // Partial translation file with 3 keys
        $parser2 = $this->createMock(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['key1', 'key2', 'key3']);
        $parser2->method('getFileName')->willReturn('partial.xlf');

        // Empty translation file
        $parser3 = $this->createMock(ParserInterface::class);
        $parser3->method('extractKeys')->willReturn([]);
        $parser3->method('getFileName')->willReturn('empty.xlf');

        $logger = $this->createMock(LoggerInterface::class);
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
        $this->assertCount(6, $issues);

        $issueArrays = array_map(fn ($issue) => $issue->toArray(), $issues);
        $keys = array_map(fn ($issue) => $issue['issues']['key'], $issueArrays);

        // All keys should be reported as missing somewhere
        $this->assertContains('key1', $keys);
        $this->assertContains('key2', $keys);
        $this->assertContains('key3', $keys);
        $this->assertContains('key4', $keys);
        $this->assertContains('key5', $keys);
        $this->assertContains('key6', $keys);

        // Verify that all three files are tracked in each issue
        foreach ($issueArrays as $issueArray) {
            $files = $issueArray['issues']['files'];
            $this->assertCount(3, $files);
        }
    }
}
