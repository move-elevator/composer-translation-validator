<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025-2026 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Validator;

use Exception;
use MoveElevator\ComposerTranslationValidator\Parser\{ParserInterface, XliffParser};
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\Translation\Util\XliffUtils;

use function sprintf;
use function strtolower;

/**
 * XliffSchemaValidator.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class XliffSchemaValidator extends AbstractValidator implements ValidatorInterface
{
    public function processFile(ParserInterface $file): array
    {
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

        try {
            $dom = XmlUtils::parse($fileContent);
        } catch (Exception $e) {
            $this->logger?->error('Failed to parse XML: '.$e->getMessage());

            return [];
        }

        // Schema validation — may throw for unsupported XLIFF versions (e.g. 2.x)
        $errors = [];
        try {
            $errors = XliffUtils::validateSchema($dom);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'No support implemented for loading XLIFF version')) {
                $this->logger?->notice(sprintf('Skipping %s: %s', $this->getShortName(), $e->getMessage()));
            } else {
                $this->logger?->error('Failed to validate XML schema: '.$e->getMessage());
            }
        }

        // Additional check: if filename encodes a locale, verify it matches target-language in the file header
        if (!$file instanceof XliffParser) {
            return $errors;
        }

        $expectedLanguage = $file->getLanguageFromFileName();
        if (null !== $expectedLanguage) {
            $targetLang = $file->getTargetLanguage();

            if (null === $targetLang) {
                $errors[] = [
                    'message' => sprintf(
                        'Missing "target-language" attribute on <file> node; expected "%s" based on filename',
                        $expectedLanguage,
                    ),
                    'level' => 'ERROR',
                ];
            } elseif (strtolower($targetLang) !== $expectedLanguage) {
                $errors[] = [
                    'message' => sprintf(
                        '"target-language" attribute "%s" does not match filename language "%s"',
                        $targetLang,
                        $expectedLanguage,
                    ),
                    'level' => 'ERROR',
                ];
            }
        }

        return $errors;
    }

    public function formatIssueMessage(Issue $issue, string $prefix = ''): string
    {
        $details = $issue->getDetails();

        // Since AbstractValidator creates one Issue per error array,
        // $details is the individual error array, not an array of errors
        if (isset($details['message'])) {
            $message = $details['message'];
            $line = isset($details['line']) ? " (Line: {$details['line']})" : '';
            $code = isset($details['code']) ? " (Code: {$details['code']})" : '';
            $level = $details['level'] ?? 'ERROR';

            $color = 'ERROR' === strtoupper((string) $level) ? 'red' : 'yellow';
            $levelText = ucfirst(strtolower((string) $level));

            return "- <fg=$color>$levelText</> {$prefix}$message$line$code";
        }

        return "- <fg=red>Error</> {$prefix}Schema validation error";
    }

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array
    {
        return [XliffParser::class];
    }
}
