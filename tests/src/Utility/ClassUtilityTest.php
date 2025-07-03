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
        $logger->method('error')->willReturnCallback(function (string|\Stringable $message) use (&$loggedMessages): void {
            $loggedMessages[] = $message;
        });

        ClassUtility::instantiate(DummyInterface::class, $logger, 'test', 'NonExistentClass');

        $this->assertStringContainsString('The class "NonExistentClass" does not exist.', $loggedMessages[0]);
        $this->assertStringContainsString('The test class "NonExistentClass" must implement MoveElevator\ComposerTranslationValidator\Tests\Utility\DummyInterface', $loggedMessages[1]);
    }

    public function testInstantiateWithClassNotImplementingInterface(): void
    {
        $loggedMessages = [];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('error')->willReturnCallback(function (string|\Stringable $message) use (&$loggedMessages): void {
            $loggedMessages[] = $message;
        });

        $this->assertNull(ClassUtility::instantiate(DummyInterface::class, $logger, 'test', InvalidClass::class));

        $this->assertStringContainsString('The test class "MoveElevator\ComposerTranslationValidator\Tests\Utility\InvalidClass" must implement MoveElevator\ComposerTranslationValidator\Tests\Utility\DummyInterface', $loggedMessages[1]);
    }

    public function testInstantiateWithValidClass(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $instance = ClassUtility::instantiate(DummyInterface::class, $logger, 'test', ValidClass::class);

        $this->assertInstanceOf(ValidClass::class, $instance);
    }
}
