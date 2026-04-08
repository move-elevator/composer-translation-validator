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

namespace MoveElevator\ComposerTranslationValidator\Tests\Service;

use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;
use MoveElevator\ComposerTranslationValidator\FileDetector\{PrefixFileDetector, SuffixFileDetector};
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Service\ValidationOrchestrationService;
use MoveElevator\ComposerTranslationValidator\Validator\{EmptyValuesValidator, KeyCountValidator, MismatchValidator, ResultType, ValidatorRegistry};
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * StandaloneUsageTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
#[CoversClass(ValidationOrchestrationService::class)]
final class StandaloneUsageTest extends TestCase
{
    private ValidationOrchestrationService $service;

    protected function setUp(): void
    {
        $this->service = new ValidationOrchestrationService(new NullLogger());
    }

    public function testBasicExampleWithNullLoggerAndDefaultConfig(): void
    {
        $config = new TranslationValidatorConfig();

        $result = $this->service->executeValidation(
            paths: [__DIR__.'/../Fixtures/translations/xliff/success'],
            excludePatterns: [],
            recursive: true,
            fileDetector: new PrefixFileDetector(),
            validators: ValidatorRegistry::getAvailableValidators(),
            config: $config,
        );

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertSame(ResultType::SUCCESS, $result->getOverallResult());
        $this->assertFalse($result->hasIssues());
    }

    public function testCustomValidatorsWithSpecificSelection(): void
    {
        $config = new TranslationValidatorConfig();

        $result = $this->service->executeValidation(
            paths: [__DIR__.'/../Fixtures/translations/yaml/success'],
            excludePatterns: [],
            recursive: false,
            fileDetector: new SuffixFileDetector(),
            validators: [
                MismatchValidator::class,
                EmptyValuesValidator::class,
                KeyCountValidator::class,
            ],
            config: $config,
        );

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertSame(ResultType::SUCCESS, $result->getOverallResult());
        $this->assertFalse($result->hasIssues());
    }

    public function testCustomValidatorSettings(): void
    {
        $config = new TranslationValidatorConfig();
        $config->setValidatorSetting('KeyCountValidator', ['threshold' => 500]);
        $config->setStrict(true);

        $result = $this->service->executeValidation(
            paths: [__DIR__.'/../Fixtures/translations/yaml/success'],
            excludePatterns: ['**/vendor/**'],
            recursive: true,
            fileDetector: new SuffixFileDetector(),
            validators: [
                MismatchValidator::class,
                EmptyValuesValidator::class,
                KeyCountValidator::class,
            ],
            config: $config,
        );

        $this->assertInstanceOf(ValidationResult::class, $result);
    }

    public function testNullFileDetectorAutoDetects(): void
    {
        $config = new TranslationValidatorConfig();

        $result = $this->service->executeValidation(
            paths: [__DIR__.'/../Fixtures/translations/yaml/success'],
            excludePatterns: [],
            recursive: false,
            fileDetector: null,
            validators: [MismatchValidator::class],
            config: $config,
        );

        $this->assertInstanceOf(ValidationResult::class, $result);
    }

    public function testResultObjectApiHasIssuesAndGetValidators(): void
    {
        $config = new TranslationValidatorConfig();

        $result = $this->service->executeValidation(
            paths: [__DIR__.'/../Fixtures/translations/yaml/fail'],
            excludePatterns: [],
            recursive: false,
            fileDetector: new SuffixFileDetector(),
            validators: [MismatchValidator::class],
            config: $config,
        );

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertTrue($result->hasIssues());
        $this->assertNotEmpty($result->getValidatorsWithIssues());

        foreach ($result->getValidatorsWithIssues() as $validator) {
            $this->assertTrue($validator->hasIssues());
            $this->assertNotEmpty($validator->getIssues());
        }
    }

    public function testResultObjectOverallResultReflectsErrors(): void
    {
        $config = new TranslationValidatorConfig();

        $result = $this->service->executeValidation(
            paths: [__DIR__.'/../Fixtures/translations/yaml/fail'],
            excludePatterns: [],
            recursive: false,
            fileDetector: new SuffixFileDetector(),
            validators: [MismatchValidator::class],
            config: $config,
        );

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertSame(ResultType::ERROR, $result->getOverallResult());
    }

    public function testReturnsNullWhenNoFilesFound(): void
    {
        $config = new TranslationValidatorConfig();

        $result = $this->service->executeValidation(
            paths: [__DIR__.'/../Fixtures/empty'],
            excludePatterns: [],
            recursive: false,
            fileDetector: new PrefixFileDetector(),
            validators: ValidatorRegistry::getAvailableValidators(),
            config: $config,
        );

        $this->assertNull($result);
    }

    public function testRecursiveValidationAcrossMultipleFormats(): void
    {
        $config = new TranslationValidatorConfig();

        $result = $this->service->executeValidation(
            paths: [__DIR__.'/../Fixtures/recursive'],
            excludePatterns: [],
            recursive: true,
            fileDetector: new PrefixFileDetector(),
            validators: [MismatchValidator::class],
            config: $config,
        );

        $this->assertInstanceOf(ValidationResult::class, $result);
    }

    public function testZeroDependencyOnPluginOrComposerRuntime(): void
    {
        $service = new ValidationOrchestrationService(new NullLogger());
        $config = new TranslationValidatorConfig();

        $result = $service->executeValidation(
            paths: [__DIR__.'/../Fixtures/translations/xliff/success'],
            excludePatterns: [],
            recursive: false,
            fileDetector: new PrefixFileDetector(),
            validators: [MismatchValidator::class],
            config: $config,
        );

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertFalse($result->hasIssues());
    }
}
