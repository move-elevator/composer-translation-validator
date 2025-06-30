<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Validator\DuplicatesValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;

final class DuplicatesValidatorTest extends TestCase
{
    public function testProcessFileWithDuplicates(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2', 'key1', 'key3', 'key2']);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new DuplicatesValidator($logger);
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

        $validator = new DuplicatesValidator($logger);
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

        $validator = new DuplicatesValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testRenderIssueSets(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = new \Symfony\Component\Console\Output\BufferedOutput();

                $issueSets = [
            'set1' => [
                [
                    'file' => 'file1.xlf',
                    'issues' => [
                        'keyA' => 2,
                        'keyB' => 3,
                    ],
                    'parser' => 'Parser\Class',
                    'type' => 'Duplicates',
                ],
            ],
            'set2' => [
                [
                    'file' => 'file2.xlf',
                    'issues' => [
                        'keyC' => 2,
                    ],
                    'parser' => 'Parser\Class',
                    'type' => 'Duplicates',
                ],
            ],
        ];

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new DuplicatesValidator($logger);
        $validator->renderIssueSets($input, $output, $issueSets);

        $expectedOutput = <<<'EOT'
+-----------+------+------------------+
| File      | Key  | Count duplicates |
+-----------+------+------------------+
| file1.xlf | keyA | 2                |
|           | keyB | 3                |
+-----------+------+------------------+
| file2.xlf | keyC | 2                |
+-----------+------+------------------+
EOT;

        $this->assertSame(trim($expectedOutput), trim($output->fetch()));
    }

    public function testExplain(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new DuplicatesValidator($logger);

        $this->assertStringContainsString('duplicate keys', $validator->explain());
    }
}
