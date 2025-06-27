<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Parser;

class ParserRegistry
{
    /**
     * @return array<int, class-string<ParserInterface>>
     */
    public static function getAvailableParsers(): array
    {
        return [
            XliffParser::class,
        ];
    }
}
