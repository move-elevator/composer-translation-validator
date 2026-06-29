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
use MoveElevator\ComposerTranslationValidator\FileDetector\PrefixFileDetector;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Service\ValidationOrchestrationService;
use MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator;
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
    public function testRecursiveValidationAcrossMultipleFormats(): void
    {
        $service = new ValidationOrchestrationService(new NullLogger());

        $result = $service->executeValidation(
            paths: [__DIR__.'/../Fixtures/recursive'],
            excludePatterns: [],
            recursive: true,
            fileDetector: new PrefixFileDetector(),
            validators: [MismatchValidator::class],
            config: new TranslationValidatorConfig(),
        );

        $this->assertInstanceOf(ValidationResult::class, $result);
    }

    public function testZeroDependencyOnPluginOrComposerRuntime(): void
    {
        $service = new ValidationOrchestrationService(new NullLogger());

        $result = $service->executeValidation(
            paths: [__DIR__.'/../Fixtures/translations/xliff/success'],
            excludePatterns: [],
            recursive: false,
            fileDetector: new PrefixFileDetector(),
            validators: [MismatchValidator::class],
            config: new TranslationValidatorConfig(),
        );

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertFalse($result->hasIssues());
    }
}
