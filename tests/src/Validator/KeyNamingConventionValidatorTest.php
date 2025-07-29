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

use InvalidArgumentException;
use Iterator;
use MoveElevator\ComposerTranslationValidator\Parser\JsonParser;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\PhpParser;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validator\KeyNamingConventionValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

final class KeyNamingConventionValidatorTest extends TestCase
{
    public function testDotNotationNotConfusedWithCamelCaseDottedKeys(): void
    {
        // Test for the bug fix: camelCase keys with dots should not be confused with dot.notation
        // This simulates the reported issue where keys like 'teaser.image.cropVariant.slider'
        // were incorrectly classified as dot.notation
        $validator = new KeyNamingConventionValidator();
        $reflection = new ReflectionClass($validator);
        $detectKeyConventions = $reflection->getMethod('detectKeyConventions');
        $detectKeyConventions->setAccessible(true);

        // Test the problematic camelCase key with dots
        $camelCaseWithDots = 'teaser.image.cropVariant.slider';
        $conventions = $detectKeyConventions->invoke($validator, $camelCaseWithDots);

        // Should be detected as camelCase, NOT as dot.notation
        $this->assertContains('camelCase', $conventions);
        $this->assertNotContains('dot.notation', $conventions);

        // Test a real dot.notation key for comparison
        $realDotNotation = 'teaser.image.variant.slider';
        $conventions2 = $detectKeyConventions->invoke($validator, $realDotNotation);

        // Should be detected as dot.notation (among others)
        $this->assertContains('dot.notation', $conventions2);
    }

    public function testOriginalReportedBugScenario(): void
    {
        // This reproduces the exact scenario from the bug report
        // where camelCase keys were incorrectly reported as inconsistent
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn([
            // Simulate a file with mostly snake_case but some camelCase dotted keys
            'some_snake_case_key',
            'another_snake_key',
            'teaser.image.cropVariant.slider',        // This was incorrectly flagged
            'teaser.image.cropVariant.default',       // This was incorrectly flagged
        ]);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $result = $validator->processFile($parser);

        // Find any issue for the camelCase dotted key
        $camelCaseIssues = array_filter($result, fn ($issue) => 'teaser.image.cropVariant.slider' === $issue['key'],
        );

        // If there is an issue, it should correctly identify the key as camelCase
        if (!empty($camelCaseIssues)) {
            $issue = array_values($camelCaseIssues)[0];
            $this->assertContains('camelCase', $issue['detected_conventions']);
            // The key should NOT be confused with dot.notation
            $this->assertNotContains('dot.notation', $issue['detected_conventions']);
        }
    }

    public function testRealDotNotationKeysAreDetectedCorrectly(): void
    {
        // Test that real dot.notation keys are still detected correctly
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn([
            'user.profile.settings',     // proper dot.notation
            'teaser.image.variant',      // proper dot.notation
            'app.config.database',       // proper dot.notation
        ]);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $result = $validator->processFile($parser);

        // All keys follow the same conventions (including dot.notation), so no issues
        $this->assertEmpty($result);
    }

    public function testConfigurationHintInAutoDetectionMode(): void
    {
        // Test that configuration hint is shown when no explicit convention is configured
        $validator = new KeyNamingConventionValidator();

        $issueData = [
            'key' => 'testKey',
            'file' => 'test.yaml',
            'detected_conventions' => ['camelCase'],
            'dominant_convention' => 'snake_case',
            'all_conventions_found' => ['camelCase', 'snake_case'],
            'inconsistency_type' => 'mixed_conventions',
        ];

        $issue = new Issue(
            $issueData['file'],
            $issueData,
            'TestParser',
            'KeyNamingConventionValidator',
        );

        $message = $validator->formatIssueMessage($issue);

        // Should contain the configuration hint
        $this->assertStringContainsString('Tip:', $message);
        $this->assertStringContainsString('Configure a specific naming convention', $message);
        $this->assertStringContainsString('snake_case, camelCase, kebab-case, PascalCase', $message);
        $this->assertStringNotContainsString('dot.notation', $message);
    }

    public function testConfigurationHintShownOnlyOnce(): void
    {
        // Test that configuration hint appears only once even with multiple issues
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['someKey', 'another_key', 'thirdKey']);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        // No convention set - should be in auto-detection mode
        $result = $validator->processFile($parser);

        // Should have multiple issues (mixed conventions)
        $this->assertNotEmpty($result);

        // Format all issue messages
        $allMessages = [];
        foreach ($result as $issueData) {
            $issue = new Issue(
                $issueData['file'],
                $issueData,
                'TestParser',
                'KeyNamingConventionValidator',
            );
            $allMessages[] = $validator->formatIssueMessage($issue);
        }

        // Count how many times the configuration hint appears
        $hintCount = 0;
        foreach ($allMessages as $message) {
            if (str_contains($message, 'Tip:')) {
                ++$hintCount;
            }
        }

        // Should appear exactly once
        $this->assertSame(1, $hintCount, 'Configuration hint should appear exactly once');

        // Also verify dot.notation is not in any hint
        $combinedMessages = implode(' ', $allMessages);
        if (str_contains($combinedMessages, 'Tip:')) {
            $this->assertStringNotContainsString('dot.notation', $combinedMessages);
        }
    }

    public function testNoConfigurationHintWithExplicitConvention(): void
    {
        // Test that no configuration hint is shown when explicit convention is configured
        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('snake_case');

        $issueData = [
            'key' => 'testKey',
            'file' => 'test.yaml',
            'expected_convention' => 'snake_case',
            'pattern' => '/^[a-z]([a-z0-9]|_[a-z0-9])*$/',
            'suggestion' => 'test_key',
        ];

        $issue = new Issue(
            $issueData['file'],
            $issueData,
            'TestParser',
            'KeyNamingConventionValidator',
        );

        $message = $validator->formatIssueMessage($issue);

        // Should NOT contain the configuration hint
        $this->assertStringNotContainsString('💡 Tip:', $message);
        $this->assertStringNotContainsString('translation-validator.yaml', $message);
    }

    public function testProcessFileWithoutConventionConfigured(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['someKey', 'another_key']);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $result = $validator->processFile($parser);

        // Should detect mixed conventions
        $this->assertNotEmpty($result);
        $this->assertEquals('mixed_conventions', $result[0]['inconsistency_type']);
    }

    public function testProcessFileWithInvalidFile(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(null);
        $parser->method('getFileName')->willReturn('invalid.yaml');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('The source file invalid.yaml is not valid.');

        $validator = new KeyNamingConventionValidator($logger);
        $validator->setConvention('snake_case');
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testSetConventionWithValidConvention(): void
    {
        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('snake_case');

        $this->assertTrue($validator->shouldRun());
    }

    public function testSetConventionWithInvalidConvention(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown convention "invalid_convention"');

        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('invalid_convention');
    }

    public function testSetConventionWithDotNotationThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('dot.notation cannot be configured explicitly');

        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('dot.notation');
    }

    public function testSetCustomPatternWithValidPattern(): void
    {
        $validator = new KeyNamingConventionValidator();
        $validator->setCustomPattern('/^[a-z]+$/');

        $this->assertTrue($validator->shouldRun());
    }

    public function testSetCustomPatternWithInvalidPattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regex pattern provided');

        $validator = new KeyNamingConventionValidator();
        $validator->setCustomPattern('invalid[pattern');
    }

    #[DataProvider('snakeCaseProvider')]
    public function testSnakeCaseValidation(string $key, bool $isValid): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn([$key]);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('snake_case');
        $result = $validator->processFile($parser);

        if ($isValid) {
            $this->assertEmpty($result);
        } else {
            $this->assertCount(1, $result);
            $this->assertEquals($key, $result[0]['key']);
        }
    }

    /**
     * @return Iterator<int, array{string, bool}>
     */
    public static function snakeCaseProvider(): Iterator
    {
        yield ['valid_key', true];
        yield ['another_valid_key', true];
        yield ['a', true];
        yield ['key123', true];
        yield ['key_123', true];
        yield ['camelCase', false];
        yield ['PascalCase', false];
        yield ['kebab-case', false];
        yield ['dot.snake_case', true]; // dots with snake_case segments are now valid
        yield ['_invalid', false];
        yield ['invalid_', false];
        yield ['invalid__key', false];
        yield ['123invalid', false];
    }

    #[DataProvider('camelCaseProvider')]
    public function testCamelCaseValidation(string $key, bool $isValid): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn([$key]);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('camelCase');
        $result = $validator->processFile($parser);

        if ($isValid) {
            $this->assertEmpty($result);
        } else {
            $this->assertCount(1, $result);
            $this->assertEquals($key, $result[0]['key']);
        }
    }

    /**
     * @return Iterator<int, array{string, bool}>
     */
    public static function camelCaseProvider(): Iterator
    {
        yield ['validKey', true];
        yield ['anotherValidKey', true];
        yield ['a', true];
        yield ['key123', true];
        yield ['keyWithNumbers123', true];
        yield ['snake_case', false];
        yield ['PascalCase', false];
        yield ['kebab-case', false];
        yield ['dot.snakeCase', true]; // dots with camelCase segments are valid
        yield ['123invalid', false];
    }

    public function testSnakeCaseWithDotsValidation(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['valid_key', 'another.valid_key', 'user.profile.settings', 'user.camelCase']);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('snake_case');
        $result = $validator->processFile($parser);

        // Should fail only for camelCase segment
        $this->assertCount(1, $result);
        $this->assertEquals('user.camelCase', $result[0]['key']);
        $this->assertEquals('user.camel_case', $result[0]['suggestion']);
    }

    public function testCustomPatternValidation(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['valid123', 'INVALID']);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setCustomPattern('/^[a-z]+[0-9]*$/'); // lowercase letters optionally followed by numbers
        $result = $validator->processFile($parser);

        $this->assertCount(1, $result);
        $this->assertEquals('INVALID', $result[0]['key']);
    }

    public function testSuggestionGeneration(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['userName', 'user-name', 'userProfile']);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('snake_case');
        $result = $validator->processFile($parser);

        $this->assertCount(3, $result);
        $this->assertEquals('user_name', $result[0]['suggestion']);
        $this->assertEquals('user_name', $result[1]['suggestion']);
        $this->assertEquals('user_profile', $result[2]['suggestion']);
    }

    public function testFormatIssueMessage(): void
    {
        $validator = new KeyNamingConventionValidator();
        $issue = new Issue(
            'test.yaml',
            [
                'key' => 'invalidKey',
                'expected_convention' => 'snake_case',
                'suggestion' => 'invalid_key',
            ],
            '',
            'KeyNamingConventionValidator',
        );

        $message = $validator->formatIssueMessage($issue, 'test: ');

        $this->assertStringContainsString('Warning', $message);
        $this->assertStringContainsString('test: key naming convention violation', $message);
        $this->assertStringContainsString('invalidKey', $message);
        $this->assertStringContainsString('snake_case', $message);
        $this->assertStringContainsString('invalid_key', $message);
    }

    public function testSupportsParser(): void
    {
        $validator = new KeyNamingConventionValidator();
        $supportedParsers = $validator->supportsParser();

        $this->assertContains(XliffParser::class, $supportedParsers);
        $this->assertContains(YamlParser::class, $supportedParsers);
        $this->assertContains(JsonParser::class, $supportedParsers);
        $this->assertContains(PhpParser::class, $supportedParsers);
    }

    public function testResultTypeOnValidationFailure(): void
    {
        $validator = new KeyNamingConventionValidator();
        $resultType = $validator->resultTypeOnValidationFailure();

        $this->assertSame(ResultType::WARNING, $resultType);
    }

    public function testShouldShowDetailedOutput(): void
    {
        $validator = new KeyNamingConventionValidator();
        $this->assertFalse($validator->shouldShowDetailedOutput());
    }

    public function testGetAvailableConventions(): void
    {
        $conventions = KeyNamingConventionValidator::getAvailableConventions();

        // Test base conventions
        $this->assertArrayHasKey('snake_case', $conventions);
        $this->assertArrayHasKey('camelCase', $conventions);
        $this->assertArrayHasKey('kebab-case', $conventions);
        $this->assertArrayHasKey('PascalCase', $conventions);

        // Test that no dot variants are listed (dots are always allowed)
        $this->assertArrayNotHasKey('dot.snake_case', $conventions);
        $this->assertArrayNotHasKey('dot.camelCase', $conventions);
        $this->assertArrayNotHasKey('dot.kebab-case', $conventions);
        $this->assertArrayNotHasKey('dot.PascalCase', $conventions);

        foreach ($conventions as $convention) {
            $this->assertNotEmpty($convention['pattern']);
            $this->assertNotEmpty($convention['description']);
        }
    }

    public function testShouldRun(): void
    {
        $validator = new KeyNamingConventionValidator();

        // Should always run, even without configuration
        $this->assertTrue($validator->shouldRun());

        // Should run with convention set
        $validator->setConvention('snake_case');
        $this->assertTrue($validator->shouldRun());

        // Should run with custom pattern set
        $validator2 = new KeyNamingConventionValidator();
        $validator2->setCustomPattern('/^[a-z]+$/');
        $this->assertTrue($validator2->shouldRun());
    }

    public function testEnumGetConfigurableConventions(): void
    {
        $configurableConventions = \MoveElevator\ComposerTranslationValidator\Enum\KeyNamingConvention::getConfigurableConventions();

        // Should contain all conventions except dot.notation
        $this->assertContains('snake_case', $configurableConventions);
        $this->assertContains('camelCase', $configurableConventions);
        $this->assertContains('kebab-case', $configurableConventions);
        $this->assertContains('PascalCase', $configurableConventions);
        $this->assertNotContains('dot.notation', $configurableConventions);

        // Should have 4 conventions (all except dot.notation)
        $this->assertCount(4, $configurableConventions);
    }

    public function testEnumFromStringRejectsDotNotation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Convention "dot.notation" is not configurable. Available conventions: snake_case, camelCase, kebab-case, PascalCase');

        \MoveElevator\ComposerTranslationValidator\Enum\KeyNamingConvention::fromString('dot.notation');
    }

    public function testToDotNotationConversion(): void
    {
        $validator = new KeyNamingConventionValidator();

        $reflection = new ReflectionClass($validator);
        $toDotNotationMethod = $reflection->getMethod('toDotNotation');
        $toDotNotationMethod->setAccessible(true);

        // Test various conversions to dot notation
        $this->assertEquals('user.name', $toDotNotationMethod->invoke($validator, 'userName'));
        $this->assertEquals('user.name', $toDotNotationMethod->invoke($validator, 'user_name'));
        $this->assertEquals('user.name', $toDotNotationMethod->invoke($validator, 'user-name'));
        $this->assertEquals('xmlhttp.request', $toDotNotationMethod->invoke($validator, 'XMLHttpRequest'));
        $this->assertEquals('user.profile.settings', $toDotNotationMethod->invoke($validator, 'userProfileSettings'));
    }

    public function testConvertDotSeparatedKeyWithNullConvention(): void
    {
        $validator = new KeyNamingConventionValidator();

        $reflection = new ReflectionClass($validator);
        $convertMethod = $reflection->getMethod('convertDotSeparatedKey');
        $convertMethod->setAccessible(true);

        // Should return original key when convention is null
        $result = $convertMethod->invoke($validator, 'user.profile', null);
        $this->assertEquals('user.profile', $result);
    }

    public function testConvertDotSeparatedKeyWithDotNotationEnum(): void
    {
        $validator = new KeyNamingConventionValidator();

        $reflection = new ReflectionClass($validator);
        $convertMethod = $reflection->getMethod('convertDotSeparatedKey');
        $convertMethod->setAccessible(true);

        // Use the DOT_NOTATION enum directly
        $dotNotationEnum = \MoveElevator\ComposerTranslationValidator\Enum\KeyNamingConvention::DOT_NOTATION;

        $result = $convertMethod->invoke($validator, 'user.profileSettings', $dotNotationEnum);
        $this->assertEquals('user.profile.settings', $result);
    }

    public function testAnalyzeKeyConsistencyWithEmptyKeys(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn([]);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $result = $validator->processFile($parser);

        // Should return empty array for empty keys
        $this->assertEmpty($result);
    }

    public function testSuggestKeyConversionWithInvalidConvention(): void
    {
        $validator = new KeyNamingConventionValidator();

        $reflection = new ReflectionClass($validator);
        $suggestMethod = $reflection->getMethod('suggestKeyConversion');
        $suggestMethod->setAccessible(true);

        // Test with invalid convention string that would throw exception
        $result = $suggestMethod->invoke($validator, 'testKey', 'invalid_convention');
        $this->assertEquals('testKey', $result); // Should return original key on exception
    }

    public function testDetectKeyConventionsReturnsMixedConventions(): void
    {
        $validator = new KeyNamingConventionValidator();

        $reflection = new ReflectionClass($validator);
        $detectMethod = $reflection->getMethod('detectKeyConventions');
        $detectMethod->setAccessible(true);

        // Test with a key that has dots but no matching conventions
        $result = $detectMethod->invoke($validator, '$pecial.ch@rs.123');
        $this->assertContains('mixed_conventions', $result);
    }

    public function testConfigurationLoadingWithInvalidConvention(): void
    {
        $config = $this->createMock(\MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig::class);
        $config->method('getValidatorSettings')
            ->with('KeyNamingConventionValidator')
            ->willReturn(['convention' => 'invalid_convention']);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Invalid convention in config'));

        $validator = new KeyNamingConventionValidator($logger);
        $validator->setConfig($config);
    }

    public function testConfigurationLoadingWithInvalidCustomPattern(): void
    {
        $config = $this->createMock(\MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig::class);
        $config->method('getValidatorSettings')
            ->with('KeyNamingConventionValidator')
            ->willReturn(['custom_pattern' => 'invalid[pattern']);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Invalid custom pattern in config'));

        $validator = new KeyNamingConventionValidator($logger);
        $validator->setConfig($config);
    }

    public function testValidateSegmentWithNullConventionReflection(): void
    {
        $validator = new KeyNamingConventionValidator();

        $reflection = new ReflectionClass($validator);
        $validateSegmentMethod = $reflection->getMethod('validateSegment');
        $validateSegmentMethod->setAccessible(true);

        // With no convention set, should return true for any segment
        $result = $validateSegmentMethod->invoke($validator, 'anySegment');
        $this->assertTrue($result);
    }

    public function testToCamelCaseHandlesPregSplitFailure(): void
    {
        $validator = new KeyNamingConventionValidator();

        $reflection = new ReflectionClass($validator);
        $toCamelCaseMethod = $reflection->getMethod('toCamelCase');
        $toCamelCaseMethod->setAccessible(true);

        // Test with various edge cases that might affect preg_split
        $result = $toCamelCaseMethod->invoke($validator, '');
        $this->assertEquals('', $result);

        $result = $toCamelCaseMethod->invoke($validator, 'single');
        $this->assertEquals('single', $result);
    }

    public function testValidateKeyFormatReturnsTrue(): void
    {
        $validator = new KeyNamingConventionValidator();

        $reflection = new ReflectionClass($validator);
        $validateKeyFormatMethod = $reflection->getMethod('validateKeyFormat');
        $validateKeyFormatMethod->setAccessible(true);

        // With no convention and no custom pattern, should return true
        $result = $validateKeyFormatMethod->invoke($validator, 'anyKey');
        $this->assertTrue($result);
    }

    public function testDetectSegmentConventionsWithUnknownPattern(): void
    {
        $validator = new KeyNamingConventionValidator();

        $reflection = new ReflectionClass($validator);
        $detectSegmentMethod = $reflection->getMethod('detectSegmentConventions');
        $detectSegmentMethod->setAccessible(true);

        // Test with a segment that matches no convention
        $result = $detectSegmentMethod->invoke($validator, '$pecial@chars123');
        $this->assertContains('unknown', $result);
    }

    public function testMixedConventionsInDominantConventionLogic(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['valid_key', '$invalid@key', 'another_valid']);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $result = $validator->processFile($parser);

        // Should detect mixed conventions and handle unknown patterns
        $this->assertNotEmpty($result);
        $this->assertEquals('mixed_conventions', $result[0]['inconsistency_type']);
    }

    #[DataProvider('conversionProvider')]
    public function testKeyConversions(string $original, string $expectedSnake, string $expectedCamel, string $expectedDot, string $expectedKebab, string $expectedPascal): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn([$original]);
        $parser->method('getFileName')->willReturn('test.yaml');

        // Test snake_case conversion
        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('snake_case');
        $result = $validator->processFile($parser);
        if (!empty($result)) {
            $this->assertEquals($expectedSnake, $result[0]['suggestion']);
        }

        // Test camelCase conversion
        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('camelCase');
        $result = $validator->processFile($parser);
        if (!empty($result)) {
            $this->assertEquals($expectedCamel, $result[0]['suggestion']);
        }

        // Test snake_case conversion (dots are always allowed)
        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('snake_case');
        $result = $validator->processFile($parser);
        if (!empty($result)) {
            $this->assertEquals($expectedDot, $result[0]['suggestion']);
        }

        // Test kebab-case conversion
        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('kebab-case');
        $result = $validator->processFile($parser);
        if (!empty($result)) {
            $this->assertEquals($expectedKebab, $result[0]['suggestion']);
        }

        // Test PascalCase conversion
        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('PascalCase');
        $result = $validator->processFile($parser);
        if (!empty($result)) {
            $this->assertEquals($expectedPascal, $result[0]['suggestion']);
        }
    }

    /**
     * @return Iterator<int, array{string, string, string, string, string, string}>
     */
    public static function conversionProvider(): Iterator
    {
        yield ['userName', 'user_name', 'userName', 'user_name', 'user-name', 'UserName'];
        yield ['user_name', 'user_name', 'userName', 'user_name', 'user-name', 'UserName'];
        yield ['user-name', 'user_name', 'userName', 'user_name', 'user-name', 'UserName'];
        yield ['user.name', 'user_name', 'userName', 'user.name', 'user-name', 'User.Name'];
        yield ['UserName', 'user_name', 'userName', 'user_name', 'user-name', 'UserName'];
        yield ['userProfileSettings', 'user_profile_settings', 'userProfileSettings', 'user_profile_settings', 'user-profile-settings', 'UserProfileSettings'];
    }

    public function testStaticGetAvailableConventions(): void
    {
        $conventions = KeyNamingConventionValidator::getAvailableConventions();

        // Test base conventions
        $this->assertArrayHasKey('snake_case', $conventions);
        $this->assertArrayHasKey('camelCase', $conventions);
        $this->assertArrayHasKey('kebab-case', $conventions);
        $this->assertArrayHasKey('PascalCase', $conventions);

        // Test that no dot variants are listed (dots are always allowed)
        $this->assertArrayNotHasKey('dot.snake_case', $conventions);
        $this->assertArrayNotHasKey('dot.camelCase', $conventions);

        // Check structure of each convention
        foreach ($conventions as $convention) {
            $this->assertNotEmpty($convention['pattern']);
            $this->assertNotEmpty($convention['description']);
        }
    }

    public function testValidatorWithInvalidSnakeCaseKeys(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['_invalid_start', 'invalid_end_', 'valid_key']);
        $parser->method('getContentByKey')->willReturnMap([
            ['_invalid_start', 'Text 1'],
            ['invalid_end_', 'Text 2'],
            ['valid_key', 'Text 3'],
        ]);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('snake_case');

        $result = $validator->processFile($parser);

        // Should have 2 issues for the invalid keys
        $this->assertCount(2, $result);
        $invalidKeys = array_column($result, 'key');
        $this->assertContains('_invalid_start', $invalidKeys);
        $this->assertContains('invalid_end_', $invalidKeys);
        $this->assertNotContains('valid_key', $invalidKeys);
    }

    public function testValidatorWithNumbersInKeys(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key123', 'user_123', '123invalid']);
        $parser->method('getContentByKey')->willReturnMap([
            ['key123', 'Text 1'],
            ['user_123', 'Text 2'],
            ['123invalid', 'Text 3'],
        ]);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('snake_case');

        $result = $validator->processFile($parser);

        // Only '123invalid' should fail (starts with number)
        $this->assertCount(1, $result);
        $this->assertEquals('123invalid', $result[0]['key']);
    }

    public function testConversionEdgeCases(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['XMLHttpRequest', 'APIKey']);
        $parser->method('getContentByKey')->willReturnMap([
            ['XMLHttpRequest', 'XML HTTP Request'],
            ['APIKey', 'API Key'],
        ]);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('snake_case');

        $result = $validator->processFile($parser);

        // Check that conversion works for complex PascalCase
        $this->assertCount(2, $result);
        $suggestions = array_column($result, 'suggestion');
        $this->assertContains('xmlhttp_request', $suggestions);
        $this->assertContains('apikey', $suggestions);
    }

    public function testMixedConventionDetection(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['valid_snake_case', 'validCamelCase', 'another_snake']);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $result = $validator->processFile($parser);

        // Should detect inconsistency for camelCase key
        $this->assertCount(1, $result);
        $this->assertEquals('validCamelCase', $result[0]['key']);
        $this->assertEquals('mixed_conventions', $result[0]['inconsistency_type']);
        $this->assertEquals('snake_case', $result[0]['dominant_convention']);
    }

    public function testConsistentConventionWithoutConfiguration(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['valid_snake_case', 'another_snake_case', 'third_snake_case']);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $result = $validator->processFile($parser);

        // Should not report issues if all keys follow the same convention
        $this->assertEmpty($result);
    }

    public function testCamelCaseWithDotsConvention(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['header.metaNavigation', 'header.userProfile']);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('camelCase');
        $result = $validator->processFile($parser);

        // Should pass validation
        $this->assertEmpty($result);
    }

    public function testCamelCaseWithDotsValidation(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['header.meta_navigation', 'header.metaNavigation']);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('camelCase');
        $result = $validator->processFile($parser);

        // Should fail for snake_case in segment
        $this->assertCount(1, $result);
        $this->assertEquals('header.meta_navigation', $result[0]['key']);
    }

    public function testCamelCaseWithDotsConversion(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['header.meta_navigation']);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('camelCase');
        $result = $validator->processFile($parser);

        $this->assertCount(1, $result);
        $this->assertEquals('header.metaNavigation', $result[0]['suggestion']);
    }

    public function testFormatIssueMessageForMixedConventions(): void
    {
        $validator = new KeyNamingConventionValidator();
        $issue = new Issue(
            'test.yaml',
            [
                'key' => 'invalidKey',
                'inconsistency_type' => 'mixed_conventions',
                'detected_conventions' => ['camelCase'],
                'dominant_convention' => 'snake_case',
                'all_conventions_found' => ['snake_case', 'camelCase'],
            ],
            '',
            'KeyNamingConventionValidator',
        );

        $message = $validator->formatIssueMessage($issue, 'test: ');

        $this->assertStringContainsString('Warning', $message);
        $this->assertStringContainsString('test: key naming inconsistency', $message);
        $this->assertStringContainsString('invalidKey', $message);
        $this->assertStringContainsString('camelCase', $message);
        $this->assertStringContainsString('snake_case', $message);
    }

    public function testGetAvailableConventionsOnlyBaseConventions(): void
    {
        $conventions = KeyNamingConventionValidator::getAvailableConventions();

        // Test that configurable conventions are available (excluding dot.notation)
        $this->assertArrayHasKey('camelCase', $conventions);
        $this->assertArrayHasKey('snake_case', $conventions);
        $this->assertArrayHasKey('kebab-case', $conventions);
        $this->assertArrayHasKey('PascalCase', $conventions);
        $this->assertArrayNotHasKey('dot.notation', $conventions);

        // Test descriptions
        $this->assertStringContainsString('camelCase', $conventions['camelCase']['description']);
    }

    public function testValidateSegmentWithNullConvention(): void
    {
        $validator = new KeyNamingConventionValidator();
        // Don't set any convention

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['anyKey']);
        $parser->method('getFileName')->willReturn('test.yaml');

        $result = $validator->processFile($parser);

        // Should return empty array since no convention is set and only one key
        $this->assertEmpty($result);
    }

    public function testDetectKeyConventionsWithDotsAndMixedSegments(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['user.profile_data', 'admin.adminPanel']);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        // No convention set - should analyze for consistency
        $result = $validator->processFile($parser);

        // Should detect mixed conventions in dot-separated keys
        $this->assertNotEmpty($result);
        $this->assertEquals('mixed_conventions', $result[0]['inconsistency_type']);
    }

    public function testDetectKeyConventionsWithUnknownPattern(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['123invalid', '$pecial_chars', 'validKey']);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        // No convention set - should analyze for consistency
        $result = $validator->processFile($parser);

        // Should detect mixed conventions when there are valid and invalid keys
        $this->assertNotEmpty($result);
        $this->assertEquals('mixed_conventions', $result[0]['inconsistency_type']);
    }

    public function testFormatIssueMessageWithEnumConvention(): void
    {
        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('snake_case');

        $details = [
            'key' => 'invalidKey',
            'suggestion' => 'invalid_key',
        ];

        $issue = new Issue(
            'test.yaml',
            $details,
            'YamlParser',
            'KeyNamingConventionValidator',
        );

        $message = $validator->formatIssueMessage($issue);

        // Just test that the method works and contains the key info
        $this->assertStringContainsString('invalidKey', $message);
        $this->assertStringContainsString('invalid_key', $message);
        $this->assertStringContainsString('convention', $message);
    }
}
