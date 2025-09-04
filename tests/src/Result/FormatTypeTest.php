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

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\FormatType;
use PHPUnit\Framework\TestCase;
use ValueError;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
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
