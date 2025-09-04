<?php

declare(strict_types=1);

/*
 * This file is part of the Composer plugin "composer-translation-validator".
 *
 * Copyright (C) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace MoveElevator\ComposerTranslationValidator\Parser;

use Psr\Log\LoggerInterface;

/**
 * ParserRegistry.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
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
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
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
