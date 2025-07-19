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

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\JsonParser;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\PhpParser;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;

class DuplicateKeysValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * @return array<string, int>
     */
    public function processFile(ParserInterface $file): array
    {
        $keys = $file->extractKeys();

        if (!$keys) {
            $this->logger?->error(
                'The source file '.$file->getFileName().' is not valid.',
            );

            return [];
        }

        $duplicateKeys = array_filter(
            array_count_values($keys),
            static fn ($count) => $count > 1,
        );
        if (!empty($duplicateKeys)) {
            return $duplicateKeys;
        }

        return [];
    }

    public function formatIssueMessage(Issue $issue, string $prefix = ''): string
    {
        $details = $issue->getDetails();
        $resultType = $this->resultTypeOnValidationFailure();

        $level = $resultType->toString();
        $color = $resultType->toColorString();

        $messages = [];
        foreach ($details as $key => $count) {
            if (is_string($key) && is_int($count)) {
                $messages[] = "- <fg=$color>$level</> {$prefix}the translation key "
                    ."`$key` occurs multiple times ({$count}x)";
            }
        }

        return implode("\n", $messages);
    }

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array
    {
        return [XliffParser::class, YamlParser::class, JsonParser::class, PhpParser::class];
    }
}
