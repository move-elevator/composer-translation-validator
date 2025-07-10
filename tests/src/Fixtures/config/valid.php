<?php

declare(strict_types=1);

use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;

return (new TranslationValidatorConfig())
    ->setPaths(['path1', 'path2'])
    ->setStrict(true)
    ->setFormat('json')
    ->setVerbose(false);
