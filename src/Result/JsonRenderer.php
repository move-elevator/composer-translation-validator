<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JsonRenderer implements RendererInterface
{
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
    }

    /**
     * Renders validation results as JSON output.
     *
     * @return int Command exit code
     *
     * @throws \JsonException
     */
    public function renderResult(): int
    {
        $result = [
            'status' => $this->resultType->resolveErrorToCommandExitCode($this->dryRun, $this->strict),
            'message' => 'Language validation succeeded.',
            'issues' => $this->issues,
        ];

        if (!empty($this->issues)) {
            $result['message'] = 'Language validation failed.';
            if ($this->dryRun) {
                $result['message'] = 'Language validation failed and completed in dry-run mode.';
            }
        }

        $this->output->writeln(
            json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $result['status'];
    }
}
