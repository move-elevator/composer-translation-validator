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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use Iterator;
use MoveElevator\ComposerTranslationValidator\Enum\KeyNamingConvention;
use MoveElevator\ComposerTranslationValidator\Validator\KeyConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * KeyConverterTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class KeyConverterTest extends TestCase
{
    private KeyConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new KeyConverter();
    }

    #[DataProvider('toSnakeCaseProvider')]
    public function testToSnakeCase(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->converter->toSnakeCase($input));
    }

    /**
     * @return Iterator<string, array{string, string}>
     */
    public static function toSnakeCaseProvider(): Iterator
    {
        yield 'camelCase' => ['userName', 'user_name'];
        yield 'PascalCase' => ['UserName', 'user_name'];
        yield 'kebab-case' => ['user-name', 'user_name'];
        yield 'dot.notation' => ['user.name', 'user_name'];
        yield 'already snake_case' => ['user_name', 'user_name'];
        yield 'complex camelCase' => ['userProfileSettings', 'user_profile_settings'];
        yield 'XMLHttpRequest' => ['XMLHttpRequest', 'xmlhttp_request'];
    }

    #[DataProvider('toCamelCaseProvider')]
    public function testToCamelCase(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->converter->toCamelCase($input));
    }

    /**
     * @return Iterator<string, array{string, string}>
     */
    public static function toCamelCaseProvider(): Iterator
    {
        yield 'snake_case' => ['user_name', 'userName'];
        yield 'kebab-case' => ['user-name', 'userName'];
        yield 'PascalCase' => ['UserName', 'userName'];
        yield 'already camelCase' => ['userName', 'userName'];
        yield 'single word' => ['single', 'single'];
        yield 'empty string' => ['', ''];
    }

    #[DataProvider('toKebabCaseProvider')]
    public function testToKebabCase(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->converter->toKebabCase($input));
    }

    /**
     * @return Iterator<string, array{string, string}>
     */
    public static function toKebabCaseProvider(): Iterator
    {
        yield 'camelCase' => ['userName', 'user-name'];
        yield 'PascalCase' => ['UserName', 'user-name'];
        yield 'snake_case' => ['user_name', 'user-name'];
        yield 'dot.notation' => ['user.name', 'user-name'];
        yield 'already kebab-case' => ['user-name', 'user-name'];
    }

    #[DataProvider('toPascalCaseProvider')]
    public function testToPascalCase(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->converter->toPascalCase($input));
    }

    /**
     * @return Iterator<string, array{string, string}>
     */
    public static function toPascalCaseProvider(): Iterator
    {
        yield 'camelCase' => ['userName', 'UserName'];
        yield 'snake_case' => ['user_name', 'UserName'];
        yield 'kebab-case' => ['user-name', 'UserName'];
        yield 'already PascalCase' => ['UserName', 'UserName'];
    }

    #[DataProvider('toDotNotationProvider')]
    public function testToDotNotation(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->converter->toDotNotation($input));
    }

    /**
     * @return Iterator<string, array{string, string}>
     */
    public static function toDotNotationProvider(): Iterator
    {
        yield 'camelCase' => ['userName', 'user.name'];
        yield 'snake_case' => ['user_name', 'user.name'];
        yield 'kebab-case' => ['user-name', 'user.name'];
        yield 'XMLHttpRequest' => ['XMLHttpRequest', 'xmlhttp.request'];
        yield 'complex camelCase' => ['userProfileSettings', 'user.profile.settings'];
    }

    public function testConvertDotSeparatedKeyWithNullConvention(): void
    {
        $result = $this->converter->convertDotSeparatedKey('user.profile', null);
        $this->assertSame('user.profile', $result);
    }

    public function testConvertDotSeparatedKeyWithSnakeCase(): void
    {
        $result = $this->converter->convertDotSeparatedKey('header.metaNavigation', KeyNamingConvention::SNAKE_CASE);
        $this->assertSame('header.meta_navigation', $result);
    }

    public function testConvertDotSeparatedKeyWithCamelCase(): void
    {
        $result = $this->converter->convertDotSeparatedKey('header.meta_navigation', KeyNamingConvention::CAMEL_CASE);
        $this->assertSame('header.metaNavigation', $result);
    }

    public function testConvertDotSeparatedKeyWithDotNotation(): void
    {
        $result = $this->converter->convertDotSeparatedKey('user.profileSettings', KeyNamingConvention::DOT_NOTATION);
        $this->assertSame('user.profile.settings', $result);
    }

    public function testConvertKeyWithDot(): void
    {
        $result = $this->converter->convertKey('header.metaNavigation', KeyNamingConvention::SNAKE_CASE);
        $this->assertSame('header.meta_navigation', $result);
    }

    public function testConvertKeyWithoutDot(): void
    {
        $result = $this->converter->convertKey('userName', KeyNamingConvention::SNAKE_CASE);
        $this->assertSame('user_name', $result);
    }
}
