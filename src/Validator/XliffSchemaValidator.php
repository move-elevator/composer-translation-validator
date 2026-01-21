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
