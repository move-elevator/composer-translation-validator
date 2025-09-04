<?php

declare(strict_types=1);

/*
 * This file is part of the Composer plugin "composer-translation-validator".
 *
 * Copyright (C) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validator\AbstractValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;

// Dummy implementation of AbstractValidator for testing purposes

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

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

        // Return an issue for some_file.xlf to test resetState functionality
        if ('some_file.xlf' === $file->getFileName()) {
            return ['validationIssue'];
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

    public function validate(array $files, ?string $parserClass): array
    {
        return parent::validate($files, $parserClass);
    }

    public function postProcess(): void
    {
        if ($this->addPostProcessIssue) {
            $this->addIssue(new Issue(
                'test_file.xlf',
                ['postProcessIssue'],
                'TestParser',
                'ConcreteValidator',
            ));
        }
    }

    public function getShortName(): string
    {
        return static::class;
    }
}

// Dummy Parser for testing

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

class TestParser implements ParserInterface
{
    public function __construct(private readonly string $filePath) {}

    public function extractKeys(): ?array
    {
        return [];
    }

    public function getContentByKey(string $key): ?string
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

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

final class AbstractValidatorTest extends TestCase
{
    private LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }

    public function testConstructorSetsLogger(): void
    {
        $validator = new ConcreteValidator($this->loggerMock);
        $reflection = new ReflectionClass($validator);
        $loggerProperty = $reflection->getProperty('logger');
        $loggerProperty->setAccessible(true);
        $this->assertSame($this->loggerMock, $loggerProperty->getValue($validator));
    }

    public function testValidateWithNoIssues(): void
    {
        $validator = new ConcreteValidator($this->loggerMock);
        $validator->addPostProcessIssue = false;
        $files = ['/path/to/clean_file.xlf'];
        $parserClass = TestParser::class;

        $result = $validator->validate($files, $parserClass);

        $this->assertEmpty($result);
    }

    /**
     * @throws ReflectionException
     */
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
                    'type' => ConcreteValidator::class,
                ],
            ],
            $result,
        );
    }

    public function testValidateWithDebugLogging(): void
    {
        /** @var MockObject&LoggerInterface $loggerMock */
        $loggerMock = $this->loggerMock;
        $loggerMock->expects($this->atLeastOnce())
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

        $this->assertContains([
            'file' => 'test_file.xlf',
            'issues' => ['postProcessIssue'],
            'parser' => 'TestParser',
            'type' => 'ConcreteValidator',
        ], $result);
    }

    public function testHasIssuesReturnsFalseWhenNoIssues(): void
    {
        $validator = new ConcreteValidator($this->loggerMock);

        $this->assertFalse($validator->hasIssues());
    }

    public function testHasIssuesReturnsTrueWhenIssuesExist(): void
    {
        $validator = new ConcreteValidator($this->loggerMock);
        $issue = new Issue(
            'test.xlf',
            ['error' => 'test'],
            'TestParser',
            'TestValidator',
        );
        $validator->addIssue($issue);

        $this->assertTrue($validator->hasIssues());
    }

    public function testGetIssuesReturnsEmptyArrayInitially(): void
    {
        $validator = new ConcreteValidator($this->loggerMock);

        $this->assertSame([], $validator->getIssues());
    }

    public function testGetIssuesReturnsAddedIssues(): void
    {
        $validator = new ConcreteValidator($this->loggerMock);
        $issue1 = new Issue(
            'file1.xlf',
            ['error1'],
            'Parser1',
            'Validator1',
        );
        $issue2 = new Issue(
            'file2.xlf',
            ['error2'],
            'Parser2',
            'Validator2',
        );

        $validator->addIssue($issue1);
        $validator->addIssue($issue2);

        $issues = $validator->getIssues();
        $this->assertCount(2, $issues);
        $this->assertContains($issue1, $issues);
        $this->assertContains($issue2, $issues);
    }

    public function testAddIssueAddsIssueToCollection(): void
    {
        $validator = new ConcreteValidator($this->loggerMock);
        $issue = new Issue(
            'test.xlf',
            ['test_error'],
            'TestParser',
            'TestValidator',
        );

        $this->assertCount(0, $validator->getIssues());

        $validator->addIssue($issue);

        $this->assertCount(1, $validator->getIssues());
        $this->assertSame($issue, $validator->getIssues()[0]);
    }

    public function testResetStateResetsIssues(): void
    {
        $validator = new ConcreteValidator($this->loggerMock);
        $issue = new Issue(
            'test.xlf',
            ['error'],
            'TestParser',
            'TestValidator',
        );
        $validator->addIssue($issue);

        $this->assertTrue($validator->hasIssues());

        // Access resetState via reflection since it's protected
        $reflection = new ReflectionClass($validator);
        $resetStateMethod = $reflection->getMethod('resetState');
        $resetStateMethod->setAccessible(true);
        $resetStateMethod->invoke($validator);

        $this->assertFalse($validator->hasIssues());
        $this->assertSame([], $validator->getIssues());
    }

    public function testValidateCallsResetStateBeforeProcessing(): void
    {
        $validator = new ConcreteValidator($this->loggerMock);

        // Add an issue first
        $issue = new Issue(
            'old.xlf',
            ['old_error'],
            'OldParser',
            'OldValidator',
        );
        $validator->addIssue($issue);

        $this->assertTrue($validator->hasIssues());

        // Call validate - this should reset state
        $files = ['/path/to/some_file.xlf'];
        $parserClass = TestParser::class;
        $result = $validator->validate($files, $parserClass);

        // The old issue should be gone, and only validation results should remain
        $issues = $validator->getIssues();
        $this->assertCount(1, $issues);
        $this->assertSame('/path/to/some_file.xlf', $issues[0]->getFile());
        $this->assertSame(['validationIssue'], $issues[0]->getDetails());
    }

    public function testValidateLogsDebugForUnsupportedParser(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->atLeastOnce())
            ->method('debug')
            ->with($this->logicalOr(
                $this->stringContains('is not supported by the validator'),
                $this->stringContains('UnsupportedValidator'),
            ));

        // Create a custom validator that doesn't support TestParser
        $validator = new
/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

class($loggerMock) extends AbstractValidator implements ValidatorInterface {
    public function processFile(ParserInterface $file): array
    {
        return [];
    }

    public function supportsParser(): array
    {
        return []; // Empty - doesn't support any parser
    }

    public function postProcess(): void {}

    public function getShortName(): string
    {
        return 'UnsupportedValidator';
    }
};

        $files = ['/path/to/test.xlf'];
        $result = $validator->validate($files, TestParser::class);

        $this->assertEmpty($result);
    }
}
