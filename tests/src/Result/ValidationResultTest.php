<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025 Konrad Michalik <km@move-elevator.de>
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

        $this->assertSame($validators, $result->getAllValidators());
        $this->assertSame($resultType, $result->getOverallResult());
        $this->assertFalse($result->hasIssues());
        $this->assertSame([], $result->getValidatorsWithIssues());
    }

    public function testConstructWithValidatorFileSetPairs(): void
    {
        $validators = [];
        $resultType = ResultType::ERROR;
        $pairs = [];

        $result = new ValidationResult($validators, $resultType, $pairs);

        $this->assertSame($validators, $result->getAllValidators());
        $this->assertSame($resultType, $result->getOverallResult());
    }

    public function testHasIssuesWithNoValidators(): void
    {
        $result = new ValidationResult([], ResultType::SUCCESS);

        $this->assertFalse($result->hasIssues());
    }

    public function testHasIssuesWithValidatorsWithoutIssues(): void
    {
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('hasIssues')->willReturn(false);

        $result = new ValidationResult([$validator], ResultType::SUCCESS);

        $this->assertFalse($result->hasIssues());
    }

    public function testHasIssuesWithValidatorsWithIssues(): void
    {
        $validator1 = $this->createStub(ValidatorInterface::class);
        $validator1->method('hasIssues')->willReturn(false);

        $validator2 = $this->createStub(ValidatorInterface::class);
        $validator2->method('hasIssues')->willReturn(true);

        $result = new ValidationResult([$validator1, $validator2], ResultType::ERROR);

        $this->assertTrue($result->hasIssues());
    }

    public function testGetValidatorsWithIssues(): void
    {
        $validator1 = $this->createStub(ValidatorInterface::class);
        $validator1->method('hasIssues')->willReturn(false);

        $validator2 = $this->createStub(ValidatorInterface::class);
        $validator2->method('hasIssues')->willReturn(true);

        $validator3 = $this->createStub(ValidatorInterface::class);
        $validator3->method('hasIssues')->willReturn(true);

        $result = new ValidationResult([$validator1, $validator2, $validator3], ResultType::WARNING);

        $validatorsWithIssues = $result->getValidatorsWithIssues();

        $this->assertCount(2, $validatorsWithIssues);
        $this->assertContains($validator2, $validatorsWithIssues);
        $this->assertContains($validator3, $validatorsWithIssues);
        $this->assertNotContains($validator1, $validatorsWithIssues);
    }

    public function testGetAllValidators(): void
    {
        $validator1 = $this->createStub(ValidatorInterface::class);
        $validator2 = $this->createStub(ValidatorInterface::class);

        $validators = [$validator1, $validator2];
        $result = new ValidationResult($validators, ResultType::SUCCESS);

        $this->assertSame($validators, $result->getAllValidators());
    }

    public function testGetOverallResult(): void
    {
        $resultType = ResultType::WARNING;
        $result = new ValidationResult([], $resultType);

        $this->assertSame($resultType, $result->getOverallResult());
    }

    public function testGetValidatorFileSetPairs(): void
    {
        $validator = $this->createStub(ValidatorInterface::class);
        $fileSet = new FileSet('TestParser', '/test/path', 'testSet', ['test.xlf']);

        $pairs = [
            ['validator' => $validator, 'fileSet' => $fileSet],
        ];

        $result = new ValidationResult([$validator], ResultType::SUCCESS, $pairs);

        $this->assertSame($pairs, $result->getValidatorFileSetPairs());
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

        $this->assertSame($validators, $result->getAllValidators());
        $this->assertSame($resultType, $result->getOverallResult());
        $this->assertSame($pairs, $result->getValidatorFileSetPairs());
        $this->assertSame($statistics, $result->getStatistics());
    }

    public function testGetStatisticsWithNullStatistics(): void
    {
        $result = new ValidationResult([], ResultType::SUCCESS);

        $this->assertNotInstanceOf(\MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics::class, $result->getStatistics());
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

        $this->assertSame($statistics, $result->getStatistics());
        $this->assertEqualsWithDelta(0.456, $result->getStatistics()->getExecutionTime(), \PHP_FLOAT_EPSILON);
        $this->assertSame(3, $result->getStatistics()->getFilesChecked());
        $this->assertSame(7, $result->getStatistics()->getKeysChecked());
        $this->assertSame(2, $result->getStatistics()->getValidatorsRun());
    }
}
