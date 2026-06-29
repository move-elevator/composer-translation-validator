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

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\FormatType;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};
use PHPUnit\Framework\TestCase;
use ValueError;

/**
 * FormatTypeTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
#[CoversClass(FormatType::class)]
final class FormatTypeTest extends TestCase
{
    public function testFromInvalidValueThrowsException(): void
    {
        $this->expectException(ValueError::class);
        FormatType::from('invalid');
    }

    #[DataProvider('validValueProvider')]
    public function testFromValidValueResolvesCase(string $input, FormatType $expected): void
    {
        self::assertSame($expected, FormatType::from($input));
    }

    /**
     * @return iterable<string, array{string, FormatType}>
     */
    public static function validValueProvider(): iterable
    {
        yield 'cli' => ['cli', FormatType::CLI];
        yield 'json' => ['json', FormatType::JSON];
        yield 'github' => ['github', FormatType::GITHUB];
    }
}
