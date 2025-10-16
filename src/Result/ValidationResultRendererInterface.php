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

/**
 * ValidationResultRendererInterface.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
interface ValidationResultRendererInterface
{
    /**
     * Render validation results and return command exit code.
     */
    public function render(ValidationResult $validationResult): int;
}
