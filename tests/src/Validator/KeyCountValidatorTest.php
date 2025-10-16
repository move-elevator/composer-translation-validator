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

use MoveElevator\ComposerTranslationValidator\Config\ConfigFactory;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Validator\{KeyCountValidator, ResultType};
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * KeyCountValidatorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class KeyCountValidatorTest extends TestCase
{
    public function testProcessFileWithExceedingKeyCount(): void
    {
        $keys = array_map(fn ($i) => "key$i", range(1, 350));

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new KeyCountValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('key_count', $result);
        $this->assertArrayHasKey('threshold', $result);
        $this->assertSame(350, $result['key_count']);
        $this->assertSame(300, $result['threshold']);
        $this->assertStringContainsString('350 translation keys', (string) $result['message']);
        $this->assertStringContainsString('threshold of 300', (string) $result['message']);
    }

    public function testProcessFileWithAcceptableKeyCount(): void
    {
        $keys = array_map(fn ($i) => "key$i", range(1, 250));

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new KeyCountValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testProcessFileWithExactThresholdKeyCount(): void
    {
        $keys = array_map(fn ($i) => "key$i", range(1, 300));

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new KeyCountValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
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

        $validator = new KeyCountValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testProcessFileWithEmptyKeys(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn([]);
        $parser->method('getFileName')->willReturn('empty.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new KeyCountValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testValidatorWithCustomThresholdFromConfig(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);
        $config->setValidatorSetting('KeyCountValidator', ['threshold' => 150]);

        $keys = array_map(fn ($i) => "key$i", range(1, 200));

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyCountValidator($logger);
        $validator->setConfig($config);
        $result = $validator->processFile($parser);

        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('key_count', $result);
        $this->assertArrayHasKey('threshold', $result);
        $this->assertSame(200, $result['key_count']);
        $this->assertSame(150, $result['threshold']);
        $this->assertStringContainsString('200 translation keys', (string) $result['message']);
        $this->assertStringContainsString('threshold of 150', (string) $result['message']);
    }

    public function testValidatorWithCustomThresholdBelowCount(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);
        $config->setValidatorSetting('KeyCountValidator', ['threshold' => 500]);

        $keys = array_map(fn ($i) => "key$i", range(1, 200));

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyCountValidator($logger);
        $validator->setConfig($config);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testValidatorWithInvalidThresholdInConfig(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);
        $config->setValidatorSetting('KeyCountValidator', ['threshold' => 'invalid']);

        $keys = array_map(fn ($i) => "key$i", range(1, 350));

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyCountValidator($logger);
        $validator->setConfig($config);
        $result = $validator->processFile($parser);

        // Should fall back to default threshold of 300
        $this->assertArrayHasKey('threshold', $result);
        $this->assertSame(300, $result['threshold']);
    }

    public function testValidatorWithoutConfig(): void
    {
        $keys = array_map(fn ($i) => "key$i", range(1, 350));

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyCountValidator($logger);
        $validator->setConfig(null);
        $result = $validator->processFile($parser);

        // Should use default threshold of 300
        $this->assertArrayHasKey('threshold', $result);
        $this->assertSame(300, $result['threshold']);
    }

    public function testValidatorWithEmptyValidatorSettings(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);
        // No validator-specific settings

        $keys = array_map(fn ($i) => "key$i", range(1, 350));

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyCountValidator($logger);
        $validator->setConfig($config);
        $result = $validator->processFile($parser);

        // Should use default threshold of 300
        $this->assertArrayHasKey('threshold', $result);
        $this->assertSame(300, $result['threshold']);
    }

    public function testSupportsParser(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyCountValidator($logger);

        $expectedParsers = [
            \MoveElevator\ComposerTranslationValidator\Parser\XliffParser::class,
            \MoveElevator\ComposerTranslationValidator\Parser\YamlParser::class,
            \MoveElevator\ComposerTranslationValidator\Parser\JsonParser::class,
            \MoveElevator\ComposerTranslationValidator\Parser\PhpParser::class,
        ];
        $this->assertSame($expectedParsers, $validator->supportsParser());
    }

    public function testGetShortName(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyCountValidator($logger);

        $this->assertSame('KeyCountValidator', $validator->getShortName());
    }

    public function testResultTypeOnValidationFailure(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyCountValidator($logger);

        $this->assertSame(ResultType::WARNING, $validator->resultTypeOnValidationFailure());
    }

    public function testFormatIssueMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyCountValidator($logger);

        // KeyCountValidator expects details to contain message, key_count, and threshold
        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
            'test.xlf',
            [
                'message' => 'File contains 350 translation keys, which exceeds the threshold of 300 keys',
                'key_count' => 350,
                'threshold' => 300,
            ],
            'XliffParser',
            'KeyCountValidator',
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Warning', $result);
        $this->assertStringContainsString('<fg=yellow>', $result);
        $this->assertStringContainsString('350 translation keys', $result);
        $this->assertStringContainsString('threshold of 300', $result);
    }

    public function testFormatIssueMessageWithPrefix(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyCountValidator($logger);

        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
            'test.xlf',
            [
                'message' => 'File contains 350 translation keys, which exceeds the threshold of 300 keys',
                'key_count' => 350,
                'threshold' => 300,
            ],
            'XliffParser',
            'KeyCountValidator',
        );

        $result = $validator->formatIssueMessage($issue, 'in file test.xlf: ');

        $this->assertStringContainsString('Warning', $result);
        $this->assertStringContainsString('in file test.xlf:', $result);
        $this->assertStringContainsString('350 translation keys', $result);
    }
}
