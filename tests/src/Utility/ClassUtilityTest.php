<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Utility;

use MoveElevator\ComposerTranslationValidator\Utility\ClassUtility;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

// Dummy interface and classes for testing
interface DummyInterface
{
}
class ValidClass implements DummyInterface
{
}
class InvalidClass
{
}

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
}
