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
use PHPUnit\Framework\TestCase;
use ValueError;

/**
 * FormatTypeTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class FormatTypeTest extends TestCase
{
    public function testFromInvalidValueThrowsException(): void
    {
        $this->expectException(ValueError::class);
        FormatType::from('invalid');
    }

    public function testEnumCases(): void
    {
        $cases = FormatType::cases();
        $this->assertContains(FormatType::CLI, $cases);
        $this->assertContains(FormatType::JSON, $cases);
        $this->assertContains(FormatType::GITHUB, $cases);
    }
}
