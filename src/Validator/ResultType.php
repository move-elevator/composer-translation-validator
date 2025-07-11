<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use Symfony\Component\Console\Command\Command;

enum ResultType: int
{
    case SUCCESS = 0;
    case WARNING = 1;
    case ERROR = 2;

    public function max(self $other): self
    {
        return $this->value >= $other->value ? $this : $other;
    }

    public function notFullySuccessful(): bool
    {
        return self::SUCCESS !== $this;
    }

    public function resolveErrorToCommandExitCode(bool $dryRun, bool $strict): int
    {
        if (self::ERROR === $this && !$dryRun) {
            return Command::FAILURE;
        }

        if (self::WARNING === $this && $strict) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    public function toString(): string
    {
        return match ($this) {
            self::SUCCESS => 'Success',
            self::WARNING => 'Warning',
            self::ERROR => 'Error',
        };
    }

    public function toColorString(): string
    {
        return match ($this) {
            self::SUCCESS => 'green',
            self::WARNING => 'yellow',
            self::ERROR => 'red',
        };
    }
}
