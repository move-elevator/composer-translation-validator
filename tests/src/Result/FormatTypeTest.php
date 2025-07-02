<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\FormatType;
use PHPUnit\Framework\TestCase;

final class FormatTypeTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('cli', FormatType::CLI->value);
        $this->assertSame('json', FormatType::JSON->value);
    }

    public function testTryFrom(): void
    {
        $this->assertSame(FormatType::CLI, FormatType::tryFrom('cli'));
        $this->assertSame(FormatType::JSON, FormatType::tryFrom('json'));
        $this->assertNull(FormatType::tryFrom('invalid'));
    }
}
