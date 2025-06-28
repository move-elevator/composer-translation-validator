<?php

declare(strict_types=1);

use MoveElevator\ComposerTranslationValidator\Command\ValidateTranslationCommand;
use Symfony\Component\Console;

$application = new Console\Application();
$application->add(new ValidateTranslationCommand());

return $application;
