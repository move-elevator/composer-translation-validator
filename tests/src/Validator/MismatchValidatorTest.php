<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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
        $reflection = new \ReflectionClass($validator);
        $keyArrayProperty = $reflection->getProperty('keyArray');
        $keyArrayProperty->setAccessible(true);
        $keyArray = $keyArrayProperty->getValue($validator);

        $this->assertEquals(
            [
                'file1.xlf' => ['key1' => null, 'key2' => null],
                'file2.xlf' => ['key2' => null, 'key3' => null],
            ],
            $keyArray
        );
    }

    public function testProcessFileWithInvalidFile(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(null);
        $parser->method('getFileName')->willReturn('invalid.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('The source file invalid.xlf is not valid.'));

        $validator = new MismatchValidator($logger);
        $result = $validator->processFile($parser);

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
        $reflection = new \ReflectionClass($validator);
        $issuesProperty = $reflection->getProperty('issues');
        $issuesProperty->setAccessible(true);
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
        $reflection = new \ReflectionClass($validator);
        $issuesProperty = $reflection->getProperty('issues');
        $issuesProperty->setAccessible(true);
        $issues = $issuesProperty->getValue($validator);

        $this->assertEmpty($issues);
    }

    public function testResetStateResetsKeyArray(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        // Manually set keyArray to simulate previous validation
        $reflection = new \ReflectionClass($validator);
        $keyArrayProperty = $reflection->getProperty('keyArray');
        $keyArrayProperty->setAccessible(true);
        $keyArrayProperty->setValue($validator, [
            'file1.xlf' => ['key1' => 'value1', 'key2' => 'value2'],
            'file2.xlf' => ['key1' => 'value1'],
        ]);

        // Verify keyArray is set
        $this->assertNotEmpty($keyArrayProperty->getValue($validator));

        // Call resetState
        $resetStateMethod = $reflection->getMethod('resetState');
        $resetStateMethod->setAccessible(true);
        $resetStateMethod->invoke($validator);

        // Verify keyArray is reset
        $this->assertSame([], $keyArrayProperty->getValue($validator));

        // Verify issues are also reset (from parent)
        $this->assertFalse($validator->hasIssues());
    }

    public function testExplain(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $this->assertStringContainsString('mismatches in translation keys', $validator->explain());
    }

    public function testSupportsParser(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $this->assertSame([
            \MoveElevator\ComposerTranslationValidator\Parser\XliffParser::class,
            \MoveElevator\ComposerTranslationValidator\Parser\YamlParser::class,
        ], $validator->supportsParser());
    }
}
