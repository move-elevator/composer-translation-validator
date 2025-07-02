<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

enum FormatType: string
{
    case CLI = 'cli';
    case JSON = 'json';
}
