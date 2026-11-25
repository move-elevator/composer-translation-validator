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

use MoveElevator\ComposerTranslationValidator\Utility\ClassUtility;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Stringable;

// Dummy interface and classes for testing

/**
 * DummyInterface.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
interface DummyInterface {}

/**
 * ValidClass.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class ValidClass implements DummyInterface {}

/**
 * InvalidClass.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class InvalidClass {}

/**
 * ClassUtilityTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ClassUtilityTest extends TestCase
{
    public function testValidateClassWithNullClass(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        self::assertTrue(ClassUtility::validateClass(DummyInterface::class, $logger, null));
    }

    public function testValidateClassWithNonExistentClass(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(self::stringContains('The class "NonExistentClass" does not exist.'));

        self::assertFalse(ClassUtility::validateClass(DummyInterface::class, $logger, 'NonExistentClass'));
    }

    public function testValidateClassWithClassNotImplementingInterface(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('The class "MoveElevator\ComposerTranslationValidator\Tests\Utility\InvalidClass" must implement MoveElevator\ComposerTranslationValidator\Tests\Utility\DummyInterface.');

        self::assertFalse(ClassUtility::validateClass(DummyInterface::class, $logger, InvalidClass::class));
    }

    public function testValidateClassWithValidClass(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        self::assertTrue(ClassUtility::validateClass(DummyInterface::class, $logger, ValidClass::class));
    }

    public function testInstantiateWithNullClass(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        self::assertNull(ClassUtility::instantiate(DummyInterface::class, $logger, 'test', null));
    }

    public function testInstantiateWithNonExistentClass(): void
    {
        $loggedMessages = [];
        $logger = self::createStub(LoggerInterface::class);
        $logger->method('error')->willReturnCallback(static function (string|Stringable $message) use (&$loggedMessages): void {
            $loggedMessages[] = $message;
        });

        ClassUtility::instantiate(DummyInterface::class, $logger, 'test', 'NonExistentClass');

        self::assertStringContainsString('The class "NonExistentClass" does not exist.', (string) $loggedMessages[0]);
        self::assertStringContainsString('The test class "NonExistentClass" must implement MoveElevator\ComposerTranslationValidator\Tests\Utility\DummyInterface', (string) $loggedMessages[1]);
    }

    public function testInstantiateWithClassNotImplementingInterface(): void
    {
        $loggedMessages = [];
        $logger = self::createStub(LoggerInterface::class);
        $logger->method('error')->willReturnCallback(static function (string|Stringable $message) use (&$loggedMessages): void {
            $loggedMessages[] = $message;
        });

        self::assertNull(ClassUtility::instantiate(DummyInterface::class, $logger, 'test', InvalidClass::class));

        self::assertStringContainsString('The test class "MoveElevator\ComposerTranslationValidator\Tests\Utility\InvalidClass" must implement MoveElevator\ComposerTranslationValidator\Tests\Utility\DummyInterface', (string) $loggedMessages[1]);
    }

    public function testInstantiateWithValidClass(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $instance = ClassUtility::instantiate(DummyInterface::class, $logger, 'test', ValidClass::class);

        self::assertInstanceOf(ValidClass::class, $instance);
    }
}
