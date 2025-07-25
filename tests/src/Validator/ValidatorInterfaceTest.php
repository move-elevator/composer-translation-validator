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

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validator\AbstractValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\TestCase;

final class ValidatorInterfaceTest extends TestCase
{
    public function testFormatIssueMessageDefault(): void
    {
        $validator = new TestValidatorImplementation();
        $issue = new Issue('test.xlf', ['message' => 'Test error'], 'TestParser', 'TestValidator');

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('Test error', $result);
        $this->assertStringContainsString('<fg=red>', $result);
    }

    public function testFormatIssueMessageWithPrefix(): void
    {
        $validator = new TestValidatorImplementation();
        $issue = new Issue('test.xlf', ['message' => 'Test error'], 'TestParser', 'TestValidator');

        $result = $validator->formatIssueMessage($issue, '(TestValidator) ');

        $this->assertStringContainsString('(TestValidator)', $result);
        $this->assertStringContainsString('Test error', $result);
    }

    public function testFormatIssueMessageWithWarningLevel(): void
    {
        $validator = new TestValidatorWithWarning();
        $issue = new Issue('test.xlf', ['message' => 'Test warning'], 'TestParser', 'TestValidator');

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Warning', $result);
        $this->assertStringContainsString('Test warning', $result);
        $this->assertStringContainsString('<fg=yellow>', $result);
    }

    public function testDistributeIssuesForDisplay(): void
    {
        $validator = new TestValidatorImplementation();

        // Add some issues
        $issue1 = new Issue('/test/path/file1.xlf', ['message' => 'Error 1'], 'TestParser', 'TestValidator');
        $issue2 = new Issue('/test/path/file2.xlf', ['message' => 'Error 2'], 'TestParser', 'TestValidator');
        $validator->addIssue($issue1);
        $validator->addIssue($issue2);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['file1.xlf', 'file2.xlf']);

        $distribution = $validator->distributeIssuesForDisplay($fileSet);

        $this->assertArrayHasKey('/test/path/file1.xlf', $distribution);
        $this->assertArrayHasKey('/test/path/file2.xlf', $distribution);
        $this->assertCount(1, $distribution['/test/path/file1.xlf']);
        $this->assertCount(1, $distribution['/test/path/file2.xlf']);
        $this->assertSame($issue1, $distribution['/test/path/file1.xlf'][0]);
        $this->assertSame($issue2, $distribution['/test/path/file2.xlf'][0]);
    }

    public function testDistributeIssuesForDisplaySkipsEmptyFilenames(): void
    {
        $validator = new TestValidatorImplementation();

        // Add issue with empty filename
        $issue = new Issue('', ['message' => 'Error'], 'TestParser', 'TestValidator');
        $validator->addIssue($issue);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', []);

        $distribution = $validator->distributeIssuesForDisplay($fileSet);

        $this->assertEmpty($distribution);
    }

    public function testShouldShowDetailedOutputDefault(): void
    {
        $validator = new TestValidatorImplementation();

        $this->assertFalse($validator->shouldShowDetailedOutput());
    }

    public function testGetShortName(): void
    {
        $validator = new TestValidatorImplementation();

        $result = $validator->getShortName();

        $this->assertSame('TestValidatorImplementation', $result);
    }
}

// Test implementation classes
class TestValidatorImplementation extends AbstractValidator
{
    public function processFile(ParserInterface $file): array
    {
        return [];
    }

    public function supportsParser(): array
    {
        return [];
    }

    public function resultTypeOnValidationFailure(): ResultType
    {
        return ResultType::ERROR;
    }
}

class TestValidatorWithWarning extends AbstractValidator
{
    public function processFile(ParserInterface $file): array
    {
        return [];
    }

    public function supportsParser(): array
    {
        return [];
    }

    public function resultTypeOnValidationFailure(): ResultType
    {
        return ResultType::WARNING;
    }
}
