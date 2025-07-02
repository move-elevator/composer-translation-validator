<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RendererFactory
{
    /**
     * @param array<class-string<ValidatorInterface>, array<string, array<string, array<mixed>>>> $issues
     *
     * @return CliRenderer|JsonRenderer|null
     */
    public static function create(
        FormatType $format,
        LoggerInterface $logger,
        OutputInterface $output,
        InputInterface $input,
        ResultType $resultType,
        array $issues,
        bool $dryRun = false,
        bool $strict = false,
    ) {
        return match ($format) {
            FormatType::CLI => new CliRenderer($logger, $output, $input, $resultType, $issues, $dryRun, $strict),
            FormatType::JSON => new JsonRenderer($logger, $output, $input, $resultType, $issues, $dryRun, $strict),
        };
    }
}
