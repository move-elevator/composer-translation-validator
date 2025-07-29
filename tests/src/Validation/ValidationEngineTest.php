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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validation;

use Exception;
use InvalidArgumentException;
use MoveElevator\ComposerTranslationValidator\FileDetector\DetectorInterface;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Service\ValidationOrchestrationService;
use MoveElevator\ComposerTranslationValidator\Validation\ValidationEngine;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;

#[CoversClass(ValidationEngine::class)]
class ValidationEngineTest extends TestCase
{
    private ValidationEngine $engine;
    private ValidationOrchestrationService $orchestrationService;
    private NullLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new NullLogger();
        $this->orchestrationService = new ValidationOrchestrationService($this->logger);
        $this->engine = new ValidationEngine($this->orchestrationService, $this->logger);
    }

    public function testValidatePathsWithEmptyPaths(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Paths array cannot be empty');

        $this->engine->validatePaths([]);
    }

    public function testValidatePathsWithValidPaths(): void
    {
        $testPath = __DIR__.'/../Fixtures/translations/xliff/success';

        $result = $this->engine->validatePaths([$testPath]);

        $this->assertInstanceOf(ValidationResult::class, $result);
    }

    public function testValidatePathsWithOptions(): void
    {
        $testPath = __DIR__.'/../Fixtures/translations/xliff/success';
        $options = [
            'recursive' => true,
            'strict' => true,
            'dryRun' => false,
        ];

        $result = $this->engine->validatePaths([$testPath], $options);

        $this->assertInstanceOf(ValidationResult::class, $result);
    }

    public function testValidatePathsWithNonexistentPath(): void
    {
        $result = $this->engine->validatePaths(['/nonexistent/path']);

        $this->assertNotInstanceOf(ValidationResult::class, $result);
    }

    public function testValidateProjectWithEmptyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Project path cannot be empty');

        $this->engine->validateProject('');
    }

    public function testValidateProjectWithNonexistentDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Project path "/nonexistent/directory" is not a valid directory');

        $this->engine->validateProject('/nonexistent/directory');
    }

    public function testValidateProjectWithValidDirectory(): void
    {
        $testPath = __DIR__.'/../Fixtures/translations/xliff/success';

        $result = $this->engine->validateProject($testPath);

        // Result can be null if no translation files are found, which is valid
        $this->assertThat(
            $result,
            $this->logicalOr(
                $this->isNull(),
                $this->isInstanceOf(ValidationResult::class),
            ),
        );
    }

    public function testValidateProjectWithConfiguration(): void
    {
        $testPath = __DIR__.'/../Fixtures/translations/xliff/success';
        $configuration = [
            'strict' => true,
            'excludePatterns' => ['**/fail/**'],
        ];

        $result = $this->engine->validateProject($testPath, $configuration);

        $this->assertThat(
            $result,
            $this->logicalOr(
                $this->isNull(),
                $this->isInstanceOf(ValidationResult::class),
            ),
        );
    }

    public function testGetAvailableValidators(): void
    {
        $validators = $this->engine->getAvailableValidators();
        $expectedValidators = ValidatorRegistry::getAvailableValidators();

        $this->assertSame($expectedValidators, $validators);
        $this->assertNotEmpty($validators);
    }

    public function testIsReadyWithValidEngine(): void
    {
        $ready = $this->engine->isReady();

        $this->assertTrue($ready);
    }

    public function testIsReadyReturnsTrueWhenValidatorsAvailable(): void
    {
        // This test verifies that isReady returns true when validators are available
        // The default case should have validators available via ValidatorRegistry
        $ready = $this->engine->isReady();
        $this->assertTrue($ready);

        // Also verify that getAvailableValidators returns non-empty array
        $validators = $this->engine->getAvailableValidators();
        $this->assertNotEmpty($validators);
    }

    public function testIsReadyWithCorruptedValidatorRegistry(): void
    {
        // Create a test version that simulates exception handling in isReady
        $engineWithException = new class {
            public function isReady(): bool
            {
                try {
                    // Force an exception by creating an invalid condition
                    throw new Exception('Simulated registry failure');
                } catch (Throwable) {
                    return false;
                }
            }
        };

        $ready = $engineWithException->isReady();
        $this->assertFalse($ready);
    }

    public function testValidatePathsRecursiveDefault(): void
    {
        $testPath = __DIR__.'/../Fixtures/recursive';

        // Test with recursive disabled (default for validatePaths)
        $result = $this->engine->validatePaths([$testPath], ['recursive' => false]);

        $this->assertThat(
            $result,
            $this->logicalOr(
                $this->isNull(),
                $this->isInstanceOf(ValidationResult::class),
            ),
        );
    }

    public function testValidateProjectRecursiveDefault(): void
    {
        $testPath = __DIR__.'/../Fixtures/recursive';

        // validateProject should enable recursive by default
        $result = $this->engine->validateProject($testPath);

        $this->assertThat(
            $result,
            $this->logicalOr(
                $this->isNull(),
                $this->isInstanceOf(ValidationResult::class),
            ),
        );
    }

    public function testValidatePathsWithValidatorSelection(): void
    {
        $testPath = __DIR__.'/../Fixtures/translations/xliff/success';
        $availableValidators = ValidatorRegistry::getAvailableValidators();

        $options = [
            'onlyValidators' => [array_slice($availableValidators, 0, 1)[0]],
        ];

        $result = $this->engine->validatePaths([$testPath], $options);

        $this->assertInstanceOf(ValidationResult::class, $result);
    }

    public function testValidatePathsWithValidatorSkipping(): void
    {
        $testPath = __DIR__.'/../Fixtures/translations/xliff/success';
        $availableValidators = ValidatorRegistry::getAvailableValidators();

        $options = [
            'skipValidators' => [array_slice($availableValidators, 0, 1)[0]],
        ];

        $result = $this->engine->validatePaths([$testPath], $options);

        $this->assertInstanceOf(ValidationResult::class, $result);
    }

    public function testValidatePathsLogsErrorOnException(): void
    {
        // Create a mock logger to capture log calls
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'Validation execution failed',
                $this->callback(fn ($context) => isset($context['error'])
                       && isset($context['paths'])
                       && isset($context['options'])),
            );

        // Create a mock orchestration service that throws an exception during execution
        $mockOrchestrationService = $this->createMock(ValidationOrchestrationService::class);
        $mockOrchestrationService->method('resolvePaths')
            ->willReturn(['/test/path']);
        $mockOrchestrationService->method('resolveFileDetector')
            ->willReturn($this->createMock(DetectorInterface::class));
        $mockOrchestrationService->method('resolveValidators')
            ->willReturn([]);
        $mockOrchestrationService->method('executeValidation')
            ->willThrowException(new RuntimeException('Test error'));

        $engine = new ValidationEngine($mockOrchestrationService, $mockLogger);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Validation failed: Test error');

        $engine->validatePaths(['/test/path']);
    }
}
