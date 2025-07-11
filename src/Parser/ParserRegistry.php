<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Parser;

use Psr\Log\LoggerInterface;

class ParserRegistry
{
    /**
     * @return array<int, class-string<ParserInterface>>
     */
    public static function getAvailableParsers(): array
    {
        return [
            XliffParser::class,
            YamlParser::class,
        ];
    }

    /**
     * @return class-string<ParserInterface>|null
     */
    public static function resolveParserClass(
        string $filePath,
        ?LoggerInterface $logger = null,
    ): ?string {
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        $parserClasses = self::getAvailableParsers();

        foreach ($parserClasses as $parserClass) {
            /* @var class-string<ParserInterface> $parserClass */
            if (in_array(
                $fileExtension,
                $parserClass::getSupportedFileExtensions(),
                true
            )) {
                return $parserClass;
            }
        }

        $logger?->warning(
            sprintf('No parser found for file: %s', $filePath)
        );

        return null;
    }
}
