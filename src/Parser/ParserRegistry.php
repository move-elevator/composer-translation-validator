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

namespace MoveElevator\ComposerTranslationValidator\Parser;

use Psr\Log\LoggerInterface;

use function in_array;
use function sprintf;

/**
 * ParserRegistry.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
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
            JsonParser::class,
            PhpParser::class,
        ];
    }

    /**
     * @return class-string<ParserInterface>|null
     */
    public static function resolveParserClass(
        string $filePath,
        ?LoggerInterface $logger = null,
    ): ?string {
        $fileExtension = pathinfo($filePath, \PATHINFO_EXTENSION);
        $parserClasses = self::getAvailableParsers();

        foreach ($parserClasses as $parserClass) {
            /* @var class-string<ParserInterface> $parserClass */
            if (in_array(
                $fileExtension,
                $parserClass::getSupportedFileExtensions(),
                true,
            )) {
                return $parserClass;
            }
        }

        $logger?->warning(
            sprintf('No parser found for file: %s', $filePath),
        );

        return null;
    }
}
