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
use MoveElevator\ComposerTranslationValidator\Parser\{JsonParser, PhpParser, XliffParser, YamlParser};
use MoveElevator\ComposerTranslationValidator\Validator\{
    AbstractValidator,
    DuplicateKeysValidator,
    DuplicateValuesValidator,
    EmptyValuesValidator,
    EncodingValidator,
    HtmlTagValidator,
    KeyCountValidator,
    KeyDepthValidator,
    KeyNamingConventionValidator,
    MismatchValidator,
    PlaceholderConsistencyValidator,
    ResultType,
    XliffSchemaValidator
};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * ValidatorMetadataTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ValidatorMetadataTest extends TestCase
{
    /**
     * @param class-string<AbstractValidator>                                                   $validatorClass
     * @param class-string<\MoveElevator\ComposerTranslationValidator\Parser\ParserInterface>[] $expectedParsers
     */
    #[DataProvider('validatorMetadataProvider')]
    public function testValidatorMetadata(
        string $validatorClass,
        string $expectedShortName,
        ResultType $expectedResultType,
        array $expectedParsers,
    ): void {
        $validator = new $validatorClass();

        $this->assertSame($expectedShortName, $validator->getShortName());
        $this->assertSame($expectedResultType, $validator->resultTypeOnValidationFailure());
        $this->assertSame($expectedParsers, $validator->supportsParser());
    }

    /**
     * @return Iterator<string, array{class-string<AbstractValidator>, string, ResultType, array<class-string<\MoveElevator\ComposerTranslationValidator\Parser\ParserInterface>>}>
     */
    public static function validatorMetadataProvider(): Iterator
    {
        $allParsers = [XliffParser::class, YamlParser::class, JsonParser::class, PhpParser::class];

        yield 'DuplicateKeysValidator' => [DuplicateKeysValidator::class, 'DuplicateKeysValidator', ResultType::ERROR, $allParsers];
        yield 'DuplicateValuesValidator' => [DuplicateValuesValidator::class, 'DuplicateValuesValidator', ResultType::WARNING, $allParsers];
        yield 'EmptyValuesValidator' => [EmptyValuesValidator::class, 'EmptyValuesValidator', ResultType::WARNING, $allParsers];
        yield 'EncodingValidator' => [EncodingValidator::class, 'EncodingValidator', ResultType::WARNING, $allParsers];
        yield 'HtmlTagValidator' => [HtmlTagValidator::class, 'HtmlTagValidator', ResultType::WARNING, $allParsers];
        yield 'KeyCountValidator' => [KeyCountValidator::class, 'KeyCountValidator', ResultType::WARNING, $allParsers];
        yield 'KeyDepthValidator' => [KeyDepthValidator::class, 'KeyDepthValidator', ResultType::WARNING, $allParsers];
        yield 'KeyNamingConventionValidator' => [KeyNamingConventionValidator::class, 'KeyNamingConventionValidator', ResultType::WARNING, $allParsers];
        yield 'MismatchValidator' => [MismatchValidator::class, 'MismatchValidator', ResultType::ERROR, $allParsers];
        yield 'PlaceholderConsistencyValidator' => [PlaceholderConsistencyValidator::class, 'PlaceholderConsistencyValidator', ResultType::WARNING, $allParsers];
        yield 'XliffSchemaValidator' => [XliffSchemaValidator::class, 'XliffSchemaValidator', ResultType::ERROR, [XliffParser::class]];
    }
}
