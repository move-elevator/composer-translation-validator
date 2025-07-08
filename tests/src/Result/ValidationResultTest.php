<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;

class ValidationResultTest extends TestCase
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
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('hasIssues')->willReturn(false);

        $result = new ValidationResult([$validator], ResultType::SUCCESS);

        $this->assertFalse($result->hasIssues());
    }

    public function testHasIssuesWithValidatorsWithIssues(): void
    {
        $validator1 = $this->createMock(ValidatorInterface::class);
        $validator1->method('hasIssues')->willReturn(false);

        $validator2 = $this->createMock(ValidatorInterface::class);
        $validator2->method('hasIssues')->willReturn(true);

        $result = new ValidationResult([$validator1, $validator2], ResultType::ERROR);

        $this->assertTrue($result->hasIssues());
    }

    public function testGetValidatorsWithIssues(): void
    {
        $validator1 = $this->createMock(ValidatorInterface::class);
        $validator1->method('hasIssues')->willReturn(false);

        $validator2 = $this->createMock(ValidatorInterface::class);
        $validator2->method('hasIssues')->willReturn(true);

        $validator3 = $this->createMock(ValidatorInterface::class);
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
        $validator1 = $this->createMock(ValidatorInterface::class);
        $validator2 = $this->createMock(ValidatorInterface::class);

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
        $validator = $this->createMock(ValidatorInterface::class);
        $fileSet = new FileSet('TestParser', '/test/path', 'testSet', ['test.xlf']);

        $pairs = [
            ['validator' => $validator, 'fileSet' => $fileSet],
        ];

        $result = new ValidationResult([$validator], ResultType::SUCCESS, $pairs);

        $this->assertSame($pairs, $result->getValidatorFileSetPairs());
    }
}
