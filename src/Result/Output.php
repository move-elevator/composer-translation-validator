<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Output
{
    protected SymfonyStyle $io;

    public function __construct(
        protected LoggerInterface $logger,
        protected OutputInterface $output,
        protected InputInterface $input,
        protected FormatType $format,
        protected ValidationResult $validationResult,
        protected bool $dryRun = false,
        protected bool $strict = false,
    ) {
        $this->io = new SymfonyStyle($this->input, $this->output);
    }

    /**
     * Summarizes validation results in the specified format.
     *
     * @return int Command exit code
     *
     * @throws \JsonException
     */
    public function summarize(): int
    {
        return match ($this->format) {
            FormatType::CLI => (new ValidationResultCliRenderer(
                $this->output,
                $this->input,
                $this->dryRun,
                $this->strict
            ))->render($this->validationResult),
            
            FormatType::JSON => (new ValidationResultJsonRenderer(
                $this->output,
                $this->dryRun,
                $this->strict
            ))->render($this->validationResult),
        };
    }
}
