<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Output
{
    protected SymfonyStyle $io;

    /**
     * @param array<class-string<ValidatorInterface>, array<string, array<string, array<mixed>>>> $issues
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected OutputInterface $output,
        protected InputInterface $input,
        protected FormatType $format,
        protected ResultType $resultType,
        protected array $issues,
        protected bool $dryRun = false,
        protected bool $strict = false,
    ) {
        $this->io = new SymfonyStyle($this->input, $this->output);
    }

    /**
     * Summarizes validation results in the specified format.
     *
     * @return int Command exit code
     */
    public function summarize(): int
    {
        $class = 'MoveElevator\\ComposerTranslationValidator\\Result\\'.ucfirst($this->format->value).'Renderer';

        if (!class_exists($class)) {
            $this->logger->error(
                'Renderer class {class} does not exist.',
                ['class' => $class]
            );

            return Command::FAILURE;
        }

        $renderer = new $class(
            $this->logger,
            $this->output,
            $this->input,
            $this->resultType,
            $this->issues,
            $this->dryRun,
            $this->strict
        );

        return $renderer->renderResult();
    }
}
