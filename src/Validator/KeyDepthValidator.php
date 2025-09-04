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

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @package ComposerTranslationValidator
 */

class KeyDepthValidator extends AbstractValidator implements ValidatorInterface
{
    private int $threshold = 8;

    public function __construct(?LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    public function setConfig(?TranslationValidatorConfig $config): void
    {
        if ($config && $config->hasValidatorSettings('KeyDepthValidator')) {
            $settings = $config->getValidatorSettings('KeyDepthValidator');
            $threshold = $settings['threshold'] ?? 8;

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

        $violatingKeys = [];
        foreach ($keys as $key) {
            $depth = $this->calculateKeyDepth($key);
            if ($depth > $this->threshold) {
                $violatingKeys[] = [
                    'key' => $key,
                    'depth' => $depth,
                    'threshold' => $this->threshold,
                ];
            }
        }

        if (!empty($violatingKeys)) {
            return [
                'message' => sprintf(
                    'Found %d translation key%s with nesting depth exceeding threshold of %d',
                    count($violatingKeys),
                    1 === count($violatingKeys) ? '' : 's',
                    $this->threshold,
                ),
                'violating_keys' => $violatingKeys,
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

    /**
     * Calculate the nesting depth of a translation key.
     * Examples:
     * - "simple" => 1
     * - "header.title" => 2
     * - "user.profile.settings.privacy" => 4.
     */
    private function calculateKeyDepth(string $key): int
    {
        // Handle empty keys
        if (empty($key)) {
            return 0;
        }

        // Count the number of separators + 1
        // Most common separators in translation keys
        $separators = ['.', '_', '-', ':'];

        $maxDepth = 1; // At least 1 level for any non-empty key

        foreach ($separators as $separator) {
            if (str_contains($key, $separator)) {
                $depth = substr_count($key, $separator) + 1;
                $maxDepth = max($maxDepth, $depth);
            }
        }

        return $maxDepth;
    }
}
