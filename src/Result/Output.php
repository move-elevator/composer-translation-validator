<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Result;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Output.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class Output
{
    public function __construct(
        protected LoggerInterface $logger,
        protected OutputInterface $output,
        protected InputInterface $input,
        protected FormatType $format,
        protected ValidationResult $validationResult,
        protected bool $dryRun = false,
        protected bool $strict = false,
    ) {}

    /**
     * Summarizes validation results in the specified format.
     *
     * @return int Command exit code
     */
    public function summarize(): int
    {
        $renderer = ValidationResultRendererFactory::create(
            $this->format,
            $this->output,
            $this->input,
            $this->dryRun,
            $this->strict,
        );

        return $renderer->render($this->validationResult);
    }
}
