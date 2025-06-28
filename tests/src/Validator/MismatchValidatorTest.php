<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;

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
                'file1.xlf' => ['key1', 'key2'],
                'file2.xlf' => ['key2', 'key3'],
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
            'key1' => [
                'file1.xlf' => 'key1',
                'file2.xlf' => null,
            ],
            'key3' => [
                'file1.xlf' => null,
                'file2.xlf' => 'key3',
            ],
        ];

        $this->assertEquals($expectedIssues, $issues);
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

    public function testRenderIssueSets(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = new \Symfony\Component\Console\Output\BufferedOutput();

        $issueSets = [
            [
                'key1' => [
                    'file1.xlf' => 'key1',
                    'file2.xlf' => null,
                ],
                'key3' => [
                    'file1.xlf' => null,
                    'file2.xlf' => 'key3',
                ],
            ],
        ];

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);
        $validator->renderIssueSets($input, $output, $issueSets);

        $expectedOutput = <<<'EOT'
+------+-----------+-----------+
| Key  | file1.xlf | file2.xlf |
+------+-----------+-----------+
| key1 | key1      | –         |
| key3 | –         | key3      |
+------+-----------+-----------+
EOT;

        $this->assertStringContainsString(trim($expectedOutput), trim($output->fetch()));
    }

    public function testExplain(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new MismatchValidator($logger);

        $this->assertStringContainsString('mismatches in translation keys', $validator->explain());
    }
}
