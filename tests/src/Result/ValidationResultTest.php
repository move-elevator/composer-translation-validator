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

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Validator\{ResultType, ValidatorInterface};
use PHPUnit\Framework\TestCase;

/**
 * ValidationResultTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ValidationResultTest extends TestCase
{
    public function testConstructWithoutValidatorFileSetPairs(): void
    {
        $validators = [];
        $resultType = ResultType::SUCCESS;

        $result = new ValidationResult($validators, $resultType);

        self::assertSame($validators, $result->getAllValidators());
        self::assertSame($resultType, $result->getOverallResult());
        self::assertFalse($result->hasIssues());
        self::assertSame([], $result->getValidatorsWithIssues());
    }

    public function testConstructWithValidatorFileSetPairs(): void
    {
        $validators = [];
        $resultType = ResultType::ERROR;
        $pairs = [];

        $result = new ValidationResult($validators, $resultType, $pairs);

        self::assertSame($validators, $result->getAllValidators());
        self::assertSame($resultType, $result->getOverallResult());
    }

    public function testHasIssuesWithNoValidators(): void
    {
        $result = new ValidationResult([], ResultType::SUCCESS);

        self::assertFalse($result->hasIssues());
    }

    public function testHasIssuesWithValidatorsWithoutIssues(): void
    {
        $validator = self::createStub(ValidatorInterface::class);
        $validator->method('hasIssues')->willReturn(false);

        $result = new ValidationResult([$validator], ResultType::SUCCESS);

        self::assertFalse($result->hasIssues());
    }

    public function testHasIssuesWithValidatorsWithIssues(): void
    {
        $validator1 = self::createStub(ValidatorInterface::class);
        $validator1->method('hasIssues')->willReturn(false);

        $validator2 = self::createStub(ValidatorInterface::class);
        $validator2->method('hasIssues')->willReturn(true);

        $result = new ValidationResult([$validator1, $validator2], ResultType::ERROR);

        self::assertTrue($result->hasIssues());
    }

    public function testGetValidatorsWithIssues(): void
    {
        $validator1 = self::createStub(ValidatorInterface::class);
        $validator1->method('hasIssues')->willReturn(false);

        $validator2 = self::createStub(ValidatorInterface::class);
        $validator2->method('hasIssues')->willReturn(true);

        $validator3 = self::createStub(ValidatorInterface::class);
        $validator3->method('hasIssues')->willReturn(true);

        $result = new ValidationResult([$validator1, $validator2, $validator3], ResultType::WARNING);

        $validatorsWithIssues = $result->getValidatorsWithIssues();

        self::assertCount(2, $validatorsWithIssues);
        self::assertContains($validator2, $validatorsWithIssues);
        self::assertContains($validator3, $validatorsWithIssues);
        self::assertNotContains($validator1, $validatorsWithIssues);
    }

    public function testGetAllValidators(): void
    {
        $validator1 = self::createStub(ValidatorInterface::class);
        $validator2 = self::createStub(ValidatorInterface::class);

        $validators = [$validator1, $validator2];
        $result = new ValidationResult($validators, ResultType::SUCCESS);

        self::assertSame($validators, $result->getAllValidators());
    }

    public function testGetOverallResult(): void
    {
        $resultType = ResultType::WARNING;
        $result = new ValidationResult([], $resultType);

        self::assertSame($resultType, $result->getOverallResult());
    }

    public function testGetValidatorFileSetPairs(): void
    {
        $validator = self::createStub(ValidatorInterface::class);
        $fileSet = new FileSet('TestParser', '/test/path', 'testSet', ['test.xlf']);

        $pairs = [
            ['validator' => $validator, 'fileSet' => $fileSet],
        ];

        $result = new ValidationResult([$validator], ResultType::SUCCESS, $pairs);

        self::assertSame($pairs, $result->getValidatorFileSetPairs());
    }

    public function testConstructWithStatistics(): void
    {
        $validators = [];
        $resultType = ResultType::SUCCESS;
        $pairs = [];
        $statistics = new \MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics(
            1.23,
            5,
            10,
            3,
        );

        $result = new ValidationResult($validators, $resultType, $pairs, $statistics);

        self::assertSame($validators, $result->getAllValidators());
        self::assertSame($resultType, $result->getOverallResult());
        self::assertSame($pairs, $result->getValidatorFileSetPairs());
        self::assertSame($statistics, $result->getStatistics());
    }

    public function testGetStatisticsWithNullStatistics(): void
    {
        $result = new ValidationResult([], ResultType::SUCCESS);

        self::assertNotInstanceOf(\MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics::class, $result->getStatistics());
    }

    public function testGetStatisticsWithProvidedStatistics(): void
    {
        $statistics = new \MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics(
            0.456,
            3,
            7,
            2,
        );

        $result = new ValidationResult([], ResultType::SUCCESS, [], $statistics);

        self::assertSame($statistics, $result->getStatistics());
        self::assertEqualsWithDelta(0.456, $result->getStatistics()->getExecutionTime(), \PHP_FLOAT_EPSILON);
        self::assertSame(3, $result->getStatistics()->getFilesChecked());
        self::assertSame(7, $result->getStatistics()->getKeysChecked());
        self::assertSame(2, $result->getStatistics()->getValidatorsRun());
    }
}
