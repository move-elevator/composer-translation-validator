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

use Exception;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\Translation\Util\XliffUtils;

class XliffSchemaValidator extends AbstractValidator implements ValidatorInterface
{
    public function processFile(ParserInterface $file): array
    {
        try {
            /*
             * With XmlUtils::loadFile() we always get a strange symfony error related to global composer autoloading issue.
             *      Call to undefined method Symfony\Component\Filesystem\Filesystem::readFile()
             */
            if (!file_exists($file->getFilePath())) {
                $this->logger?->error('File does not exist: '.$file->getFileName());

                return [];
            }

            $fileContent = file_get_contents($file->getFilePath());
            if (false === $fileContent) {
                $this->logger?->error('Failed to read file: '.$file->getFileName());

                return [];
            }
            $dom = XmlUtils::parse($fileContent);
            $errors = XliffUtils::validateSchema($dom);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'No support implemented for loading XLIFF version')) {
                $this->logger?->notice(sprintf('Skipping %s: %s', $this->getShortName(), $e->getMessage()));
            } else {
                $this->logger?->error('Failed to validate XML schema: '.$e->getMessage());
            }

            return [];
        }

        if (!empty($errors)) {
            return $errors;
        }

        return [];
    }

    public function formatIssueMessage(Issue $issue, string $prefix = ''): string
    {
        $details = $issue->getDetails();
        $messages = [];

        foreach ($details as $error) {
            if (is_array($error)) {
                $message = $error['message'] ?? 'Schema validation error';
                $line = isset($error['line']) ? " (Line: {$error['line']})" : '';
                $code = isset($error['code']) ? " (Code: {$error['code']})" : '';
                $level = $error['level'] ?? 'ERROR';

                $color = 'ERROR' === strtoupper($level) ? 'red' : 'yellow';
                $levelText = ucfirst(strtolower($level));

                $messages[] = "- <fg=$color>$levelText</> {$prefix}$message$line$code";
            }
        }

        if (empty($messages)) {
            $messages[] = "- <fg=red>Error</> {$prefix}Schema validation error";
        }

        return implode("\n", $messages);
    }

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array
    {
        return [XliffParser::class];
    }
}
