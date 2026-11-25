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

use MoveElevator\ComposerTranslationValidator\Config\ConfigFactory;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Validator\KeyCountValidator;
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
        $keys = array_map(static fn ($i) => "key$i", range(1, 350));

        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $validator = new KeyCountValidator($logger);
        $result = $validator->processFile($parser);

        self::assertArrayHasKey('message', $result);
        self::assertArrayHasKey('key_count', $result);
        self::assertArrayHasKey('threshold', $result);
        self::assertSame(350, $result['key_count']);
        self::assertSame(300, $result['threshold']);
        self::assertStringContainsString('350 translation keys', (string) $result['message']);
        self::assertStringContainsString('threshold of 300', (string) $result['message']);
    }

    public function testProcessFileWithAcceptableKeyCount(): void
    {
        $keys = array_map(static fn ($i) => "key$i", range(1, 250));

        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $validator = new KeyCountValidator($logger);
        $result = $validator->processFile($parser);

        self::assertEmpty($result);
    }

    public function testProcessFileWithExactThresholdKeyCount(): void
    {
        $keys = array_map(static fn ($i) => "key$i", range(1, 300));

        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $validator = new KeyCountValidator($logger);
        $result = $validator->processFile($parser);

        self::assertEmpty($result);
    }

    public function testProcessFileWithInvalidFile(): void
    {
        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(null);
        $parser->method('getFileName')->willReturn('invalid.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(self::stringContains('The source file invalid.xlf is not valid.'));

        $validator = new KeyCountValidator($logger);
        $result = $validator->processFile($parser);

        self::assertEmpty($result);
    }

    public function testProcessFileWithEmptyKeys(): void
    {
        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn([]);
        $parser->method('getFileName')->willReturn('empty.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $validator = new KeyCountValidator($logger);
        $result = $validator->processFile($parser);

        self::assertEmpty($result);
    }

    public function testValidatorWithCustomThresholdFromConfig(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);
        $config->setValidatorSetting('KeyCountValidator', ['threshold' => 150]);

        $keys = array_map(static fn ($i) => "key$i", range(1, 200));

        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new KeyCountValidator($logger);
        $validator->setConfig($config);
        $result = $validator->processFile($parser);

        self::assertArrayHasKey('message', $result);
        self::assertArrayHasKey('key_count', $result);
        self::assertArrayHasKey('threshold', $result);
        self::assertSame(200, $result['key_count']);
        self::assertSame(150, $result['threshold']);
        self::assertStringContainsString('200 translation keys', (string) $result['message']);
        self::assertStringContainsString('threshold of 150', (string) $result['message']);
    }

    public function testValidatorWithCustomThresholdBelowCount(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);
        $config->setValidatorSetting('KeyCountValidator', ['threshold' => 500]);

        $keys = array_map(static fn ($i) => "key$i", range(1, 200));

        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new KeyCountValidator($logger);
        $validator->setConfig($config);
        $result = $validator->processFile($parser);

        self::assertEmpty($result);
    }

    public function testValidatorWithInvalidThresholdInConfig(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);
        $config->setValidatorSetting('KeyCountValidator', ['threshold' => 'invalid']);

        $keys = array_map(static fn ($i) => "key$i", range(1, 350));

        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new KeyCountValidator($logger);
        $validator->setConfig($config);
        $result = $validator->processFile($parser);

        // Should fall back to default threshold of 300
        self::assertArrayHasKey('threshold', $result);
        self::assertSame(300, $result['threshold']);
    }

    public function testValidatorWithoutConfig(): void
    {
        $keys = array_map(static fn ($i) => "key$i", range(1, 350));

        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new KeyCountValidator($logger);
        $validator->setConfig(null);
        $result = $validator->processFile($parser);

        // Should use default threshold of 300
        self::assertArrayHasKey('threshold', $result);
        self::assertSame(300, $result['threshold']);
    }

    public function testValidatorWithEmptyValidatorSettings(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);
        // No validator-specific settings

        $keys = array_map(static fn ($i) => "key$i", range(1, 350));

        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new KeyCountValidator($logger);
        $validator->setConfig($config);
        $result = $validator->processFile($parser);

        // Should use default threshold of 300
        self::assertArrayHasKey('threshold', $result);
        self::assertSame(300, $result['threshold']);
    }

    public function testFormatIssueMessage(): void
    {
        $logger = self::createStub(LoggerInterface::class);
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

        self::assertStringContainsString('Warning', $result);
        self::assertStringContainsString('<fg=yellow>', $result);
        self::assertStringContainsString('350 translation keys', $result);
        self::assertStringContainsString('threshold of 300', $result);
    }
}
