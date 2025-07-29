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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validation\Result;

use InvalidArgumentException;
use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validation\Result\ValidatorFileSetPair;
use MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidatorFileSetPair::class)]
class ValidatorFileSetPairTest extends TestCase
{
    private MismatchValidator $validator;
    private FileSet $fileSet;

    protected function setUp(): void
    {
        $this->validator = new MismatchValidator();
        $this->fileSet = new FileSet('XliffParser', '/test/path', 'test-set', ['/test/file1.xlf', '/test/file2.xlf']);
    }

    public function testConstructor(): void
    {
        $pair = new ValidatorFileSetPair($this->validator, $this->fileSet);

        $this->assertSame($this->validator, $pair->validator);
        $this->assertSame($this->fileSet, $pair->fileSet);
    }

    public function testGetValidatorName(): void
    {
        $pair = new ValidatorFileSetPair($this->validator, $this->fileSet);

        $this->assertSame(MismatchValidator::class, $pair->getValidatorName());
    }

    public function testHasIssues(): void
    {
        $pair = new ValidatorFileSetPair($this->validator, $this->fileSet);

        // Initially no issues
        $this->assertFalse($pair->hasIssues());

        // Add an issue to the validator
        $issue = new Issue('/test/file1.xlf', ['Test issue'], 'TestParser', 'MismatchValidator');
        $this->validator->addIssue($issue);
        $this->assertTrue($pair->hasIssues());
    }

    public function testGetFileSetId(): void
    {
        $pair = new ValidatorFileSetPair($this->validator, $this->fileSet);

        $this->assertSame('test-set', $pair->getFileSetId());
    }

    public function testGetFiles(): void
    {
        $pair = new ValidatorFileSetPair($this->validator, $this->fileSet);

        $expected = ['/test/file1.xlf', '/test/file2.xlf'];
        $this->assertSame($expected, $pair->getFiles());
    }

    public function testToArray(): void
    {
        $pair = new ValidatorFileSetPair($this->validator, $this->fileSet);

        $array = $pair->toArray();

        $this->assertSame($this->validator, $array['validator']);
        $this->assertSame($this->fileSet, $array['fileSet']);
    }

    public function testFromArrayValid(): void
    {
        $array = [
            'validator' => $this->validator,
            'fileSet' => $this->fileSet,
        ];

        $pair = ValidatorFileSetPair::fromArray($array);

        $this->assertSame($this->validator, $pair->validator);
        $this->assertSame($this->fileSet, $pair->fileSet);
    }

    public function testFromArrayMissingValidator(): void
    {
        $array = [
            'fileSet' => $this->fileSet,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array must contain validator and fileSet keys');

        ValidatorFileSetPair::fromArray($array);
    }

    public function testFromArrayMissingFileSet(): void
    {
        $array = [
            'validator' => $this->validator,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array must contain validator and fileSet keys');

        ValidatorFileSetPair::fromArray($array);
    }

    public function testFromArrayInvalidValidator(): void
    {
        $array = [
            'validator' => 'not-a-validator',
            'fileSet' => $this->fileSet,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('validator must implement ValidatorInterface');

        ValidatorFileSetPair::fromArray($array);
    }

    public function testFromArrayInvalidFileSet(): void
    {
        $array = [
            'validator' => $this->validator,
            'fileSet' => 'not-a-fileset',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('fileSet must be instance of FileSet');

        ValidatorFileSetPair::fromArray($array);
    }

    public function testRoundTripConversion(): void
    {
        $original = new ValidatorFileSetPair($this->validator, $this->fileSet);
        $array = $original->toArray();
        $converted = ValidatorFileSetPair::fromArray($array);

        $this->assertSame($original->validator, $converted->validator);
        $this->assertSame($original->fileSet, $converted->fileSet);
        $this->assertSame($original->getValidatorName(), $converted->getValidatorName());
        $this->assertSame($original->getFileSetId(), $converted->getFileSetId());
        $this->assertSame($original->getFiles(), $converted->getFiles());
    }
}
