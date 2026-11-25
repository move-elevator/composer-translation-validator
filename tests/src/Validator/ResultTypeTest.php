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
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;

/**
 * ResultTypeTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ResultTypeTest extends TestCase
{
    public function testMax(): void
    {
        self::assertSame(ResultType::ERROR, ResultType::ERROR->max(ResultType::WARNING));
        self::assertSame(ResultType::ERROR, ResultType::WARNING->max(ResultType::ERROR));
        self::assertSame(ResultType::WARNING, ResultType::WARNING->max(ResultType::SUCCESS));
        self::assertSame(ResultType::WARNING, ResultType::SUCCESS->max(ResultType::WARNING));
        self::assertSame(ResultType::SUCCESS, ResultType::SUCCESS->max(ResultType::SUCCESS));
    }

    public function testNotFullySuccessful(): void
    {
        self::assertFalse(ResultType::SUCCESS->notFullySuccessful());
        self::assertTrue(ResultType::WARNING->notFullySuccessful());
        self::assertTrue(ResultType::ERROR->notFullySuccessful());
    }

    public function testResolveErrorToCommandExitCode(): void
    {
        // ERROR, not dryRun, not strict
        self::assertSame(Command::FAILURE, ResultType::ERROR->resolveErrorToCommandExitCode(false, false));

        // ERROR, dryRun, not strict
        self::assertSame(Command::SUCCESS, ResultType::ERROR->resolveErrorToCommandExitCode(true, false));

        // ERROR, not dryRun, strict
        self::assertSame(Command::FAILURE, ResultType::ERROR->resolveErrorToCommandExitCode(false, true));

        // ERROR, dryRun, strict
        self::assertSame(Command::SUCCESS, ResultType::ERROR->resolveErrorToCommandExitCode(true, true));

        // WARNING, not dryRun, not strict
        self::assertSame(Command::SUCCESS, ResultType::WARNING->resolveErrorToCommandExitCode(false, false));

        // WARNING, dryRun, not strict
        self::assertSame(Command::SUCCESS, ResultType::WARNING->resolveErrorToCommandExitCode(true, false));

        // WARNING, not dryRun, strict
        self::assertSame(Command::FAILURE, ResultType::WARNING->resolveErrorToCommandExitCode(false, true));

        // WARNING, dryRun, strict
        self::assertSame(Command::FAILURE, ResultType::WARNING->resolveErrorToCommandExitCode(true, true));

        // SUCCESS, not dryRun, not strict
        self::assertSame(Command::SUCCESS, ResultType::SUCCESS->resolveErrorToCommandExitCode(false, false));

        // SUCCESS, dryRun, not strict
        self::assertSame(Command::SUCCESS, ResultType::SUCCESS->resolveErrorToCommandExitCode(true, false));

        // SUCCESS, not dryRun, strict
        self::assertSame(Command::SUCCESS, ResultType::SUCCESS->resolveErrorToCommandExitCode(false, true));

        // SUCCESS, dryRun, strict
        self::assertSame(Command::SUCCESS, ResultType::SUCCESS->resolveErrorToCommandExitCode(true, true));
    }

    #[DataProvider('stringRepresentationProvider')]
    public function testStringRepresentations(ResultType $type, string $expectedLabel, string $expectedColor): void
    {
        self::assertSame($expectedLabel, $type->toString());
        self::assertSame($expectedColor, $type->toColorString());
    }

    /**
     * @return Iterator<string, array{ResultType, string, string}>
     */
    public static function stringRepresentationProvider(): Iterator
    {
        yield 'success' => [ResultType::SUCCESS, 'Success', 'green'];
        yield 'warning' => [ResultType::WARNING, 'Warning', 'yellow'];
        yield 'error' => [ResultType::ERROR, 'Error', 'red'];
    }

    public function testMaxWithSameValues(): void
    {
        self::assertSame(ResultType::ERROR, ResultType::ERROR->max(ResultType::ERROR));
        self::assertSame(ResultType::WARNING, ResultType::WARNING->max(ResultType::WARNING));
    }
}
