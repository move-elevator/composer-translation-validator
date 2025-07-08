<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Validator\DuplicateKeysValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class DuplicateKeysValidatorTest extends TestCase
{
    public function testProcessFileWithDuplicates(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2', 'key1', 'key3', 'key2']);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new DuplicateKeysValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertSame(['key1' => 2, 'key2' => 2], $result);
    }

    public function testProcessFileWithoutDuplicates(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2', 'key3']);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new DuplicateKeysValidator($logger);
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

        $validator = new DuplicateKeysValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testExplain(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new DuplicateKeysValidator($logger);

        $this->assertStringContainsString('duplicate keys', $validator->explain());
    }

    public function testSupportsParser(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new DuplicateKeysValidator($logger);

        $this->assertSame([\MoveElevator\ComposerTranslationValidator\Parser\XliffParser::class], $validator->supportsParser());
    }
}
