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

/**
 * DuplicateValuesValidator.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
class DuplicateValuesValidator extends AbstractValidator implements ValidatorInterface
{
    /** @var array<string, array<string, array<int, string>>> */
    protected array $valuesArray = [];

    /**
     * @return array<string, int>
     */
    public function processFile(ParserInterface $file): array
    {
        $keys = $file->extractKeys();

        if (!$keys) {
            $this->logger?->error('The source file '.$file->getFileName().' is not valid.');

            return [];
        }

        foreach ($keys as $key) {
            $value = $file->getContentByKey($key);
            if (null === $value) {
                continue;
            }
            $fileKey = !empty($this->currentFilePath) ? $this->currentFilePath : $file->getFileName();
            $this->valuesArray[$fileKey][$value][] = $key;
        }

        return [];
    }

    public function postProcess(): void
    {
        foreach ($this->valuesArray as $file => $valueKeyArray) {
            $duplicates = [];
            foreach ($valueKeyArray as $value => $keys) {
                if (count(array_unique($keys)) > 1) {
                    $duplicates[$value] = array_unique($keys);
                }
            }
            if (!empty($duplicates)) {
                $this->addIssue(new Issue(
                    $file,
                    $duplicates,
                    '',
                    $this->getShortName(),
                ));
            }
        }
    }

    public function formatIssueMessage(Issue $issue, string $prefix = ''): string
    {
        $details = $issue->getDetails();
        $resultType = $this->resultTypeOnValidationFailure();

        $level = $resultType->toString();
        $color = $resultType->toColorString();

        $messages = [];
        foreach ($details as $value => $keys) {
            if (is_string($value) && is_array($keys)) {
                $keyList = implode('`, `', $keys);
                $messages[] = "- <fg=$color>$level</> {$prefix}the translation value "
                    ."`$value` occurs in multiple keys (`$keyList`)";
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

    protected function resetState(): void
    {
        parent::resetState();
        $this->valuesArray = [];
    }

    public function resultTypeOnValidationFailure(): ResultType
    {
        return ResultType::WARNING;
    }
}
