<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\FormatType;
use PHPUnit\Framework\TestCase;

final class FormatTypeTest extends TestCase
{
    public function testFromInvalidValueThrowsException(): void
    {
        $this->expectException(\ValueError::class);
        FormatType::from('invalid');
    }

    public function testEnumCases(): void
    {
        $cases = FormatType::cases();
        $this->assertContains(FormatType::CLI, $cases);
        $this->assertContains(FormatType::JSON, $cases);
    }
}
