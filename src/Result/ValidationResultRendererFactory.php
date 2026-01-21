<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025-2026 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Result;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ValidationResultRendererFactory.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
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
            FormatType::GITHUB => new ValidationResultGitHubRenderer($output, $dryRun, $strict),
        };
    }
}
