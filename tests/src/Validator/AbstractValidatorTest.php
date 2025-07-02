<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Validator\AbstractValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

// Dummy implementation of AbstractValidator for testing purposes
class ConcreteValidator extends AbstractValidator implements ValidatorInterface
{
    public bool $addPostProcessIssue = false;

    /**
     * @return array<mixed>
     */
    public function processFile(ParserInterface $file): array
    {
        // Simulate some validation logic
        if ('file_with_issues.xlf' === $file->getFileName()) {
            return ['issue1', 'issue2'];
        }

        return [];
    }

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array
    {
        return [TestParser::class];
    }

    public function explain(): string
    {
        return 'This is a concrete validator for testing.';
    }

    public function validate(array $files, ?string $parserClass): array
    {
        return parent::validate($files, $parserClass);
    }

    public function renderIssueSets(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output, array $issueSets): void
    {
        // Dummy implementation for testing AbstractValidator
    }

    public function postProcess(): void
    {
        if ($this->addPostProcessIssue) {
            $reflection = new \ReflectionClass($this);
            $issuesProperty = $reflection->getProperty('issues');
            $issuesProperty->setAccessible(true);
            $currentIssues = $issuesProperty->getValue($this);
            $currentIssues[] = ['postProcessIssue'];
            $issuesProperty->setValue($this, $currentIssues);
        }
    }
}

// Dummy Parser for testing
class TestParser implements ParserInterface
{
    public function __construct(private readonly string $filePath)
    {
    }

    public function extractKeys(): ?array
    {
        return [];
    }

    public function getContentByKey(string $key, string $attribute = 'source'): ?string
    {
        return null;
    }

    public static function getSupportedFileExtensions(): array
    {
        return ['xlf'];
    }

    public function getFileName(): string
    {
        return basename($this->filePath);
    }

    public function getFileDirectory(): string
    {
        return dirname($this->filePath);
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getLanguage(): string
    {
        return '';
    }
}

final class AbstractValidatorTest extends TestCase
{
    private MockObject|LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }

    public function testConstructorSetsLogger(): void
    {
        $validator = new ConcreteValidator($this->loggerMock);
        $reflection = new \ReflectionClass($validator);
        $loggerProperty = $reflection->getProperty('logger');
        $loggerProperty->setAccessible(true);
        $this->assertSame($this->loggerMock, $loggerProperty->getValue($validator));
    }

    public function testValidateWithNoIssues(): void
    {
        $validator = new ConcreteValidator($this->loggerMock);
        $validator->addPostProcessIssue = false;
        $files = ['/path/to/some_file.xlf'];
        $parserClass = TestParser::class;

        $result = $validator->validate($files, $parserClass);

        $this->assertEmpty($result);
    }

    public function testValidateWithIssues(): void
    {
        $validator = new ConcreteValidator($this->loggerMock);
        $validator->addPostProcessIssue = false;
        $files = ['file_with_issues.xlf'];
        $parserClass = TestParser::class;

        $result = $validator->validate($files, $parserClass);

        /* @phpstan-ignore-next-line method.impossibleType */
        $this->assertSame(
            [
                [
                    'file' => 'file_with_issues.xlf',
                    'issues' => ['issue1', 'issue2'],
                    'parser' => TestParser::class,
                    'type' => 'ConcreteValidator',
                ],
            ],
            $result
        );
    }

    public function testValidateWithDebugLogging(): void
    {
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('debug');

        $validator = new ConcreteValidator($this->loggerMock);
        $files = ['/path/to/some_file.xlf'];
        $parserClass = TestParser::class;

        $validator->validate($files, $parserClass);
    }

    public function testPostProcessAddsIssue(): void
    {
        $validator = new ConcreteValidator($this->loggerMock);
        $validator->addPostProcessIssue = true;
        $files = ['/path/to/some_file.xlf'];
        $parserClass = TestParser::class;

        $result = $validator->validate($files, $parserClass);

        $this->assertContains(['postProcessIssue'], $result);
    }
}
