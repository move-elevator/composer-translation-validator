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

namespace MoveElevator\ComposerTranslationValidator\Tests\Utility;

use MoveElevator\ComposerTranslationValidator\Enum\LocaleMatch;
use MoveElevator\ComposerTranslationValidator\Utility\LocaleUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * LocaleUtilityTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class LocaleUtilityTest extends TestCase
{
    /**
     * @param array{base: string, region: ?string} $expected
     */
    #[DataProvider('parseProvider')]
    public function testParse(string $locale, array $expected): void
    {
        self::assertSame($expected, LocaleUtility::parse($locale));
    }

    /**
     * @return iterable<string, array{string, array{base: string, region: ?string}}>
     */
    public static function parseProvider(): iterable
    {
        yield 'base only' => ['de', ['base' => 'de', 'region' => null]];
        yield 'hyphen region' => ['de-AT', ['base' => 'de', 'region' => 'AT']];
        yield 'underscore region' => ['de_DE', ['base' => 'de', 'region' => 'DE']];
        yield 'lowercase region' => ['de-at', ['base' => 'de', 'region' => 'AT']];
        yield 'mixed case base' => ['DE_de', ['base' => 'de', 'region' => 'DE']];
        yield 'empty' => ['', ['base' => '', 'region' => null]];
    }

    #[DataProvider('compareProvider')]
    public function testCompare(string $a, string $b, LocaleMatch $expected): void
    {
        self::assertSame($expected, LocaleUtility::compare($a, $b));
    }

    /**
     * @return iterable<string, array{string, string, LocaleMatch}>
     */
    public static function compareProvider(): iterable
    {
        yield 'identical base' => ['de', 'de', LocaleMatch::Identical];
        yield 'identical full locale' => ['de-AT', 'de_AT', LocaleMatch::Identical];
        yield 'identical case-insensitive' => ['DE-at', 'de_AT', LocaleMatch::Identical];
        yield 'region mismatch' => ['de-AT', 'de_DE', LocaleMatch::RegionMismatch];
        yield 'base mismatch' => ['de', 'fr', LocaleMatch::BaseMismatch];
        yield 'base mismatch with regions' => ['de-AT', 'fr-FR', LocaleMatch::BaseMismatch];
        yield 'missing region one side is not a mismatch' => ['de', 'de-AT', LocaleMatch::Identical];
        yield 'missing region other side is not a mismatch' => ['de-AT', 'de', LocaleMatch::Identical];
    }
}
