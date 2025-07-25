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

final class KeyNamingConventionValidatorTest extends TestCase
{
    public function testProcessFileWithoutConventionConfigured(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['someKey', 'another_key']);

        $validator = new KeyNamingConventionValidator();
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
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
        yield ['dot.notation', false];
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
        yield ['dot.notation', false];
        yield ['123invalid', false];
    }

    #[DataProvider('dotNotationProvider')]
    public function testDotNotationValidation(string $key, bool $isValid): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn([$key]);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('dot.notation');
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
    public static function dotNotationProvider(): Iterator
    {
        yield ['valid.key', true];
        yield ['another.valid.key', true];
        yield ['a', true];
        yield ['key123', true];
        yield ['user.profile.name', true];
        yield ['snake_case', false];
        yield ['camelCase', false];
        yield ['PascalCase', false];
        yield ['kebab-case', false];
        yield ['.invalid', false];
        yield ['invalid.', false];
        yield ['invalid..key', false];
        yield ['123invalid', false];
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
        $parser->method('extractKeys')->willReturn(['userName', 'user-name', 'user.name']);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('snake_case');
        $result = $validator->processFile($parser);

        $this->assertCount(3, $result);
        $this->assertEquals('user_name', $result[0]['suggestion']);
        $this->assertEquals('user_name', $result[1]['suggestion']);
        $this->assertEquals('user_name', $result[2]['suggestion']);
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

        $this->assertArrayHasKey('snake_case', $conventions);
        $this->assertArrayHasKey('camelCase', $conventions);
        $this->assertArrayHasKey('dot.notation', $conventions);
        $this->assertArrayHasKey('kebab-case', $conventions);
        $this->assertArrayHasKey('PascalCase', $conventions);

        foreach ($conventions as $convention) {
            $this->assertNotEmpty($convention['pattern']);
            $this->assertNotEmpty($convention['description']);
        }
    }

    public function testShouldRun(): void
    {
        $validator = new KeyNamingConventionValidator();

        // Should not run without configuration
        $this->assertFalse($validator->shouldRun());

        // Should run with convention set
        $validator->setConvention('snake_case');
        $this->assertTrue($validator->shouldRun());

        // Should run with custom pattern set
        $validator2 = new KeyNamingConventionValidator();
        $validator2->setCustomPattern('/^[a-z]+$/');
        $this->assertTrue($validator2->shouldRun());
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

        // Test dot.notation conversion
        $validator = new KeyNamingConventionValidator();
        $validator->setConvention('dot.notation');
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
        yield ['userName', 'user_name', 'userName', 'user.name', 'user-name', 'UserName'];
        yield ['user_name', 'user_name', 'userName', 'user.name', 'user-name', 'UserName'];
        yield ['user-name', 'user_name', 'userName', 'user.name', 'user-name', 'UserName'];
        yield ['user.name', 'user_name', 'userName', 'user.name', 'user-name', 'UserName'];
        yield ['UserName', 'user_name', 'userName', 'user.name', 'user-name', 'UserName'];
        yield ['userProfileSettings', 'user_profile_settings', 'userProfileSettings', 'user.profile.settings', 'user-profile-settings', 'UserProfileSettings'];
    }

    public function testStaticGetAvailableConventions(): void
    {
        $conventions = KeyNamingConventionValidator::getAvailableConventions();

        $this->assertArrayHasKey('snake_case', $conventions);
        $this->assertArrayHasKey('camelCase', $conventions);
        $this->assertArrayHasKey('dot.notation', $conventions);
        $this->assertArrayHasKey('kebab-case', $conventions);
        $this->assertArrayHasKey('PascalCase', $conventions);

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
}
