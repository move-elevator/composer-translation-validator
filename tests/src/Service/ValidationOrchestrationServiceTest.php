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

namespace MoveElevator\ComposerTranslationValidator\Tests\Service;

use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;
use MoveElevator\ComposerTranslationValidator\FileDetector\PrefixFileDetector;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Service\ValidationOrchestrationService;
use MoveElevator\ComposerTranslationValidator\Validator\{MismatchValidator, ValidatorInterface, ValidatorRegistry};
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

use function array_slice;

/**
 * ValidationOrchestrationServiceTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
#[CoversClass(ValidationOrchestrationService::class)]
/**
 * ValidationOrchestrationServiceTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ValidationOrchestrationServiceTest extends TestCase
{
    private ValidationOrchestrationService $service;

    protected function setUp(): void
    {
        $this->service = new ValidationOrchestrationService(new NullLogger());
    }

    public function testExecuteValidationWithEmptyPaths(): void
    {
        $result = $this->service->executeValidation(
            [],
            [],
            false,
            null,
            [MismatchValidator::class],
            new TranslationValidatorConfig(),
        );

        $this->assertNotInstanceOf(ValidationResult::class, $result);
    }

    public function testExecuteValidationWithNoFilesFound(): void
    {
        $result = $this->service->executeValidation(
            ['/nonexistent/path'],
            [],
            false,
            null,
            [MismatchValidator::class],
            new TranslationValidatorConfig(),
        );

        $this->assertNotInstanceOf(ValidationResult::class, $result);
    }

    public function testExecuteValidationWithValidFiles(): void
    {
        $testPath = __DIR__.'/../Fixtures/translations/xliff/success';

        $result = $this->service->executeValidation(
            [$testPath],
            [],
            false,
            new PrefixFileDetector(),
            [MismatchValidator::class],
            new TranslationValidatorConfig(),
        );

        $this->assertInstanceOf(ValidationResult::class, $result);
    }

    public function testExecuteValidationWithRecursiveSearch(): void
    {
        $testPath = __DIR__.'/../Fixtures/recursive';

        $result = $this->service->executeValidation(
            [$testPath],
            [],
            true,
            new PrefixFileDetector(),
            [MismatchValidator::class],
            new TranslationValidatorConfig(),
        );

        $this->assertInstanceOf(ValidationResult::class, $result);
    }

    public function testExecuteValidationWithExcludePatterns(): void
    {
        $testPath = __DIR__.'/../Fixtures/translations/xliff';

        $result = $this->service->executeValidation(
            [$testPath],
            ['**/fail/**'],
            true,
            new PrefixFileDetector(),
            [MismatchValidator::class],
            new TranslationValidatorConfig(),
        );

        $this->assertInstanceOf(ValidationResult::class, $result);
    }

    public function testResolveFileDetectorWithConfiguredDetector(): void
    {
        $config = new TranslationValidatorConfig();
        $config->setFileDetectors([PrefixFileDetector::class]);

        $detector = $this->service->resolveFileDetector($config);

        $this->assertInstanceOf(PrefixFileDetector::class, $detector);
    }

    public function testResolveFileDetectorWithEmptyConfig(): void
    {
        $config = new TranslationValidatorConfig();

        $detector = $this->service->resolveFileDetector($config);

        $this->assertNotInstanceOf(\MoveElevator\ComposerTranslationValidator\FileDetector\DetectorInterface::class, $detector);
    }

    public function testResolveFileDetectorWithInvalidClass(): void
    {
        $config = new TranslationValidatorConfig();
        $config->setFileDetectors(['NonExistentClass']);

        $detector = $this->service->resolveFileDetector($config);

        $this->assertNotInstanceOf(\MoveElevator\ComposerTranslationValidator\FileDetector\DetectorInterface::class, $detector);
    }

    public function testResolveValidatorsWithOnlyParameter(): void
    {
        $only = [MismatchValidator::class];
        $validators = $this->service->resolveValidators($only);

        $this->assertSame($only, $validators);
    }

    public function testResolveValidatorsWithSkipParameter(): void
    {
        $skip = [MismatchValidator::class];
        $allValidators = ValidatorRegistry::getAvailableValidators();
        $expected = array_values(array_diff($allValidators, $skip));

        $validators = $this->service->resolveValidators(null, $skip);

        $this->assertEquals($expected, $validators);
    }

    public function testResolveValidatorsWithConfig(): void
    {
        $config = new TranslationValidatorConfig();
        $config->setOnly([MismatchValidator::class]);

        $validators = $this->service->resolveValidators(null, null, $config);

        $this->assertSame([MismatchValidator::class], $validators);
    }

    public function testResolveValidatorsWithConfigSkip(): void
    {
        $config = new TranslationValidatorConfig();
        $config->setSkip([MismatchValidator::class]);
        $allValidators = ValidatorRegistry::getAvailableValidators();
        $expected = array_values(array_diff($allValidators, [MismatchValidator::class]));

        $validators = $this->service->resolveValidators(null, null, $config);

        $this->assertEquals($expected, $validators);
    }

    public function testResolveValidatorsWithParameterOverridingConfig(): void
    {
        $config = new TranslationValidatorConfig();
        $config->setOnly([MismatchValidator::class]);

        // Parameter should override config
        $parameterOnly = ValidatorRegistry::getAvailableValidators();
        $validators = $this->service->resolveValidators($parameterOnly, null, $config);

        $this->assertEquals($parameterOnly, $validators);
    }

    public function testResolveValidatorsWithDefaultBehavior(): void
    {
        $validators = $this->service->resolveValidators();
        $expected = ValidatorRegistry::getAvailableValidators();

        $this->assertEquals($expected, $validators);
    }

    public function testResolvePathsWithInputPaths(): void
    {
        $inputPaths = ['/absolute/path', 'relative/path'];
        $config = new TranslationValidatorConfig();
        $config->setPaths(['config/path']);

        $resolvedPaths = $this->service->resolvePaths($inputPaths, $config);

        $this->assertEquals('/absolute/path', $resolvedPaths[0]);
        $this->assertStringEndsWith('/relative/path', $resolvedPaths[1]);
    }

    public function testResolvePathsWithConfigPaths(): void
    {
        $inputPaths = [];
        $config = new TranslationValidatorConfig();
        $config->setPaths(['/absolute/config', 'relative/config']);

        $resolvedPaths = $this->service->resolvePaths($inputPaths, $config);

        $this->assertEquals('/absolute/config', $resolvedPaths[0]);
        $this->assertStringEndsWith('/relative/config', $resolvedPaths[1]);
    }

    public function testResolvePathsWithEmptyInput(): void
    {
        $inputPaths = [];
        $config = new TranslationValidatorConfig();

        $resolvedPaths = $this->service->resolvePaths($inputPaths, $config);

        $this->assertEmpty($resolvedPaths);
    }

    public function testValidateClassInputWithNullInput(): void
    {
        $result = $this->service->validateClassInput(
            ValidatorInterface::class,
            'validator',
            null,
        );

        $this->assertEmpty($result);
    }

    public function testValidateClassInputWithSingleClass(): void
    {
        $result = $this->service->validateClassInput(
            ValidatorInterface::class,
            'validator',
            MismatchValidator::class,
        );

        $this->assertSame([MismatchValidator::class], $result);
    }

    public function testValidateClassInputWithMultipleClasses(): void
    {
        $validators = ValidatorRegistry::getAvailableValidators();
        $input = implode(',', array_slice($validators, 0, 2));

        $result = $this->service->validateClassInput(
            ValidatorInterface::class,
            'validator',
            $input,
        );

        $this->assertCount(2, $result);
        $this->assertEquals(array_slice($validators, 0, 2), $result);
    }

    public function testValidateClassInputWithInvalidClass(): void
    {
        // Invalid classes are logged as errors but still returned in the array
        // This matches the original behavior from ValidateTranslationCommand
        $result = $this->service->validateClassInput(
            ValidatorInterface::class,
            'validator',
            'NonExistentValidator',
        );

        $this->assertSame(['NonExistentValidator'], $result);
    }
}
