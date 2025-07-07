<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use MoveElevator\ComposerTranslationValidator\Validator\DuplicateValuesValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

final class DuplicateValuesValidatorTest extends TestCase
{
    private MockObject|LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }

    public function testProcessFileWithDuplicateValues(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2', 'key3']);
        $parser->method('getContentByKey')
            ->willReturnMap([
                ['key1', 'source', 'valueA'],
                ['key2', 'source', 'valueB'],
                ['key3', 'source', 'valueA'], // Duplicate value
            ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $validator = new DuplicateValuesValidator($this->loggerMock);
        $validator->processFile($parser);

        // Access protected property to check internal state
        $reflection = new \ReflectionClass($validator);
        $valuesArrayProperty = $reflection->getProperty('valuesArray');
        $valuesArrayProperty->setAccessible(true);
        $valuesArray = $valuesArrayProperty->getValue($validator);

        $this->assertArrayHasKey('test.xlf', $valuesArray);
        $this->assertArrayHasKey('valueA', $valuesArray['test.xlf']);
        $this->assertSame(['key1', 'key3'], $valuesArray['test.xlf']['valueA']);
    }

    public function testProcessFileWithoutDuplicateValues(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2', 'key3']);
        $parser->method('getContentByKey')
            ->willReturnMap([
                ['key1', 'source', 'valueA'],
                ['key2', 'source', 'valueB'],
                ['key3', 'source', 'valueC'],
            ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $validator = new DuplicateValuesValidator($this->loggerMock);
        $validator->processFile($parser);

        $reflection = new \ReflectionClass($validator);
        $valuesArrayProperty = $reflection->getProperty('valuesArray');
        $valuesArrayProperty->setAccessible(true);
        $valuesArray = $valuesArrayProperty->getValue($validator);

        $this->assertArrayHasKey('test.xlf', $valuesArray);
        $this->assertArrayHasKey('valueA', $valuesArray['test.xlf']);
        $this->assertArrayHasKey('valueB', $valuesArray['test.xlf']);
        $this->assertArrayHasKey('valueC', $valuesArray['test.xlf']);
        $this->assertSame(['key1'], $valuesArray['test.xlf']['valueA']);
        $this->assertSame(['key2'], $valuesArray['test.xlf']['valueB']);
        $this->assertSame(['key3'], $valuesArray['test.xlf']['valueC']);
    }

    public function testProcessFileWithInvalidFile(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(null);
        $parser->method('getFileName')->willReturn('invalid.xlf');

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('The source file invalid.xlf is not valid.'));

        $validator = new DuplicateValuesValidator($this->loggerMock);
        $validator->processFile($parser);

        $reflection = new \ReflectionClass($validator);
        $valuesArrayProperty = $reflection->getProperty('valuesArray');
        $valuesArrayProperty->setAccessible(true);
        $valuesArray = $valuesArrayProperty->getValue($validator);

        $this->assertEmpty($valuesArray);
    }

    public function testPostProcessWithDuplicateValues(): void
    {
        $validator = new DuplicateValuesValidator($this->loggerMock);

        // Manually set valuesArray to simulate previous processFile calls
        $reflection = new \ReflectionClass($validator);
        $valuesArrayProperty = $reflection->getProperty('valuesArray');
        $valuesArrayProperty->setAccessible(true);
        $valuesArrayProperty->setValue($validator, [
            'file1.xlf' => [
                'valueA' => ['key1', 'key3'],
                'valueB' => ['key2'],
            ],
            'file2.xlf' => [
                'valueX' => ['keyA', 'keyB'],
            ],
        ]);

        $validator->postProcess();

        $issuesProperty = $reflection->getProperty('issues');
        $issuesProperty->setAccessible(true);
        $issues = $issuesProperty->getValue($validator);

        $expectedIssues = [
            [
                'file' => 'file1.xlf',
                'issues' => [
                    'valueA' => ['key1', 'key3'],
                ],
                'parser' => '',
                'type' => 'DuplicateValuesValidator',
            ],
            [
                'file' => 'file2.xlf',
                'issues' => [
                    'valueX' => ['keyA', 'keyB'],
                ],
                'parser' => '',
                'type' => 'DuplicateValuesValidator',
            ],
        ];

        $this->assertSame($expectedIssues, array_map(fn ($issue) => $issue->toArray(), $issues));
    }

    public function testPostProcessWithoutDuplicateValues(): void
    {
        $validator = new DuplicateValuesValidator($this->loggerMock);

        // Manually set valuesArray with no duplicates
        $reflection = new \ReflectionClass($validator);
        $valuesArrayProperty = $reflection->getProperty('valuesArray');
        $valuesArrayProperty->setAccessible(true);
        $valuesArrayProperty->setValue($validator, [
            'file1.xlf' => [
                'valueA' => ['key1'],
                'valueB' => ['key2'],
            ],
        ]);

        $validator->postProcess();

        $issuesProperty = $reflection->getProperty('issues');
        $issuesProperty->setAccessible(true);
        $issues = $issuesProperty->getValue($validator);

        $this->assertEmpty($issues);
    }

    public function testResetStateResetsValuesArray(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new DuplicateValuesValidator($logger);

        // Manually set valuesArray to simulate previous validation
        $reflection = new \ReflectionClass($validator);
        $valuesArrayProperty = $reflection->getProperty('valuesArray');
        $valuesArrayProperty->setAccessible(true);
        $valuesArrayProperty->setValue($validator, [
            'file1.xlf' => [
                'value1' => ['key1', 'key2'],
                'value2' => ['key3'],
            ],
            'file2.xlf' => [
                'value1' => ['keyA'],
            ],
        ]);

        // Verify valuesArray is set
        $this->assertNotEmpty($valuesArrayProperty->getValue($validator));

        // Call resetState
        $resetStateMethod = $reflection->getMethod('resetState');
        $resetStateMethod->setAccessible(true);
        $resetStateMethod->invoke($validator);

        // Verify valuesArray is reset
        $this->assertSame([], $valuesArrayProperty->getValue($validator));

        // Verify issues are also reset (from parent)
        $this->assertFalse($validator->hasIssues());
    }

    public function testRenderIssueSets(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = new BufferedOutput();

        $issueSets = [
            'file1.xlf' => [
                [
                    'file' => 'file1.xlf',
                    'issues' => [
                        'valueA' => ['key1', 'key3'],
                    ],
                ],
            ],
            'file2.xlf' => [
                [
                    'file' => 'file2.xlf',
                    'issues' => [
                        'valueX' => ['keyA', 'keyB'],
                    ],
                ],
            ],
        ];

        $validator = new DuplicateValuesValidator($this->loggerMock);
        $validator->renderIssueSets($input, $output, $issueSets);

        $expectedOutput = <<<'EOT'
+-----------+------+--------+
| File      | Key  | Value  |
+-----------+------+--------+
| file1.xlf | key1 | valueA |
|           | key3 |        |
+-----------+------+--------+
| file2.xlf | keyA | valueX |
|           | keyB |        |
+-----------+------+--------+
EOT;

        $this->assertSame(trim($expectedOutput), trim($output->fetch()));
    }

    public function testExplain(): void
    {
        $validator = new DuplicateValuesValidator($this->loggerMock);
        $this->assertStringContainsString('duplicate values', $validator->explain());
    }

    public function testSupportsParser(): void
    {
        $validator = new DuplicateValuesValidator($this->loggerMock);
        $supportedParsers = $validator->supportsParser();

        $this->assertContains(XliffParser::class, $supportedParsers);
        $this->assertContains(YamlParser::class, $supportedParsers);
    }

    public function testResultTypeOnValidationFailure(): void
    {
        $validator = new DuplicateValuesValidator($this->loggerMock);
        $this->assertSame(ResultType::WARNING, $validator->resultTypeOnValidationFailure());
    }
}
