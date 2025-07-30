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

use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;
use MoveElevator\ComposerTranslationValidator\Parser\JsonParser;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\PhpParser;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use Psr\Log\LoggerInterface;

class KeyCountValidator extends AbstractValidator implements ValidatorInterface
{
    private int $threshold = 300;

    public function __construct(?LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    public function setConfig(?TranslationValidatorConfig $config): void
    {
        if ($config && $config->hasValidatorSettings('KeyCountValidator')) {
            $settings = $config->getValidatorSettings('KeyCountValidator');
            $threshold = $settings['threshold'] ?? 300;

            if (is_numeric($threshold) && (int) $threshold > 0) {
                $this->threshold = (int) $threshold;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function processFile(ParserInterface $file): array
    {
        $keys = $file->extractKeys();

        if (null === $keys) {
            $this->logger?->error(
                'The source file '.$file->getFileName().' is not valid.',
            );

            return [];
        }

        $keyCount = count($keys);

        if ($keyCount > $this->threshold) {
            return [
                'message' => sprintf(
                    'File contains %d translation keys, which exceeds the threshold of %d keys',
                    $keyCount,
                    $this->threshold,
                ),
                'key_count' => $keyCount,
                'threshold' => $this->threshold,
            ];
        }

        return [];
    }

    public function resultTypeOnValidationFailure(): ResultType
    {
        return ResultType::WARNING;
    }

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array
    {
        return [XliffParser::class, YamlParser::class, JsonParser::class, PhpParser::class];
    }
}
