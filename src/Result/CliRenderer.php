<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

use MoveElevator\ComposerTranslationValidator\Utility\PathUtility;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CliRenderer implements RendererInterface
{
    protected SymfonyStyle $io;

    /**
     * @param array<class-string<ValidatorInterface>, array<string, array<string, array<mixed>>>> $issues
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected OutputInterface $output,
        protected InputInterface $input,
        protected ResultType $resultType,
        protected array $issues,
        protected bool $dryRun = false,
        protected bool $strict = false,
    ) {
        $this->io = new SymfonyStyle($this->input, $this->output);
    }

    public function renderResult(): int
    {
        $this->renderIssues();
        if ($this->resultType->notFullySuccessful()) {
            $this->io->newLine();
            $this->io->{$this->dryRun || ResultType::WARNING === $this->resultType ? 'warning' : 'error'}(
                $this->dryRun
                    ? 'Language validation failed and completed in dry-run mode.'
                    : 'Language validation failed.'
            );
        } else {
            $message = 'Language validation succeeded.';
            $this->output->isVerbose()
                ? $this->io->success($message)
                : $this->output->writeln('<fg=green>'.$message.'</>');
        }

        return $this->resultType->resolveErrorToCommandExitCode($this->dryRun, $this->strict);
    }

    /**
     * Renders validation issues using validator-specific formatters.
     */
    private function renderIssues(): void
    {
        foreach ($this->issues as $validator => $paths) {
            $validatorInstance = new $validator($this->logger);
            /* @var ValidatorInterface $validatorInstance */

            $this->io->section(sprintf('Validator: <fg=cyan>%s</>', $validator));
            foreach ($paths as $path => $sets) {
                if ($this->output->isVerbose()) {
                    $this->io->writeln(sprintf('Explanation: %s', $validatorInstance->explain()));
                }
                $this->io->writeln(sprintf('<fg=gray>Folder Path: %s</>', PathUtility::normalizeFolderPath($path)));
                $this->io->newLine();
                $validatorInstance->renderIssueSets(
                    $this->input,
                    $this->output,
                    $sets
                );

                $this->io->newLine();
                $this->io->newLine();
            }
        }
    }
}
