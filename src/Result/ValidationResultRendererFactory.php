<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidationResultRendererFactory
{
    public static function create(
        FormatType $format,
        OutputInterface $output,
        InputInterface $input,
        bool $dryRun = false,
        bool $strict = false,
    ): ValidationResultRendererInterface {
        return match ($format) {
            FormatType::CLI => new ValidationResultCliRenderer($output, $input, $dryRun, $strict),
            FormatType::JSON => new ValidationResultJsonRenderer($output, $dryRun, $strict),
        };
    }
}
