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

use MoveElevator\ComposerTranslationValidator\Command\ValidateTranslationCommand;
use Symfony\Component\Console;

$application = new Console\Application();
$application->add(new ValidateTranslationCommand());

return $application;
