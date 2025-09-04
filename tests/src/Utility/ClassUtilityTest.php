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

namespace MoveElevator\ComposerTranslationValidator\Tests\Utility;

use MoveElevator\ComposerTranslationValidator\Utility\ClassUtility;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Stringable;

// Dummy interface and classes for testing
interface DummyInterface {}

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

class ValidClass implements DummyInterface {}

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

class InvalidClass {}

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

final class ClassUtilityTest extends TestCase
{
    public function testValidateClassWithNullClass(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $this->assertTrue(ClassUtility::validateClass(DummyInterface::class, $logger, null));
    }

    public function testValidateClassWithNonExistentClass(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('The class "NonExistentClass" does not exist.'));

        $this->assertFalse(ClassUtility::validateClass(DummyInterface::class, $logger, 'NonExistentClass'));
    }

    public function testValidateClassWithClassNotImplementingInterface(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('The class "MoveElevator\ComposerTranslationValidator\Tests\Utility\InvalidClass" must implement MoveElevator\ComposerTranslationValidator\Tests\Utility\DummyInterface.');

        $this->assertFalse(ClassUtility::validateClass(DummyInterface::class, $logger, InvalidClass::class));
    }

    public function testValidateClassWithValidClass(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $this->assertTrue(ClassUtility::validateClass(DummyInterface::class, $logger, ValidClass::class));
    }

    public function testInstantiateWithNullClass(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $this->assertNull(ClassUtility::instantiate(DummyInterface::class, $logger, 'test', null));
    }

    public function testInstantiateWithNonExistentClass(): void
    {
        $loggedMessages = [];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('error')->willReturnCallback(function (string|Stringable $message) use (&$loggedMessages): void {
            $loggedMessages[] = $message;
        });

        ClassUtility::instantiate(DummyInterface::class, $logger, 'test', 'NonExistentClass');

        $this->assertStringContainsString('The class "NonExistentClass" does not exist.', (string) $loggedMessages[0]);
        $this->assertStringContainsString('The test class "NonExistentClass" must implement MoveElevator\ComposerTranslationValidator\Tests\Utility\DummyInterface', (string) $loggedMessages[1]);
    }

    public function testInstantiateWithClassNotImplementingInterface(): void
    {
        $loggedMessages = [];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('error')->willReturnCallback(function (string|Stringable $message) use (&$loggedMessages): void {
            $loggedMessages[] = $message;
        });

        $this->assertNull(ClassUtility::instantiate(DummyInterface::class, $logger, 'test', InvalidClass::class));

        $this->assertStringContainsString('The test class "MoveElevator\ComposerTranslationValidator\Tests\Utility\InvalidClass" must implement MoveElevator\ComposerTranslationValidator\Tests\Utility\DummyInterface', (string) $loggedMessages[1]);
    }

    public function testInstantiateWithValidClass(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $instance = ClassUtility::instantiate(DummyInterface::class, $logger, 'test', ValidClass::class);

        $this->assertInstanceOf(ValidClass::class, $instance);
    }
}
