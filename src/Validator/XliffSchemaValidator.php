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

use DOMDocument;
use Exception;
use MoveElevator\ComposerTranslationValidator\Enum\LocaleMatch;
use MoveElevator\ComposerTranslationValidator\Parser\{ParserInterface, XliffParser};
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Utility\LocaleUtility;
use ReflectionMethod;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\Translation\Util\XliffUtils;
use Throwable;

use function is_string;
use function sprintf;

/**
 * XliffSchemaValidator.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class XliffSchemaValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * Prepared XLIFF schema source per version, cached for the whole process.
     *
     * @var array<string, string>
     */
    private static array $schemaSourceCache = [];

    private static ?ReflectionMethod $getSchemaMethod = null;

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
        // @codeCoverageIgnoreStart
        if (false === $fileContent) {
            $this->logger?->error('Failed to read file: '.$file->getFileName());

            return [];
        }
        // @codeCoverageIgnoreEnd

        try {
            $dom = XmlUtils::parse($fileContent);
        } catch (Exception $e) {
            $this->logger?->error('Failed to parse XML: '.$e->getMessage());

            return [];
        }

        // Schema validation — may throw for unsupported XLIFF versions (e.g. 2.x)
        $errors = [];
        try {
            $errors = $this->validateSchema($dom);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'No support implemented for loading XLIFF version')) {
                $this->logger?->notice(sprintf('Skipping %s: %s', $this->getShortName(), $e->getMessage()));
            } else {
                // @codeCoverageIgnoreStart
                $this->logger?->error('Failed to validate XML schema: '.$e->getMessage());
                // @codeCoverageIgnoreEnd
            }
        }

        // Additional check: if filename encodes a locale, verify it matches target-language in the file header
        if (!$file instanceof XliffParser) {
            return $errors;
        }

        $expectedLocale = $file->getLocaleFromFileName();
        if (null !== $expectedLocale) {
            $targetLocale = $file->getTargetLocale();
            $isVersion2 = $file->isVersion2();
            $attribute = $isVersion2 ? 'trgLang' : 'target-language';
            $element = $isVersion2 ? '<xliff>' : '<file>';

            if (null === $targetLocale) {
                $errors[] = [
                    'message' => sprintf(
                        'Missing "%s" attribute on %s node; expected "%s" based on filename',
                        $attribute,
                        $element,
                        $expectedLocale,
                    ),
                    'level' => 'ERROR',
                ];
            } else {
                $match = LocaleUtility::compare($targetLocale, $expectedLocale);

                if (LocaleMatch::BaseMismatch === $match) {
                    $errors[] = [
                        'message' => sprintf(
                            '"%s" attribute "%s" does not match filename language "%s"',
                            $attribute,
                            $targetLocale,
                            $expectedLocale,
                        ),
                        'level' => 'ERROR',
                    ];
                } elseif (LocaleMatch::RegionMismatch === $match) {
                    $errors[] = [
                        'message' => sprintf(
                            '"%s" attribute "%s" has a different region than filename locale "%s"',
                            $attribute,
                            $targetLocale,
                            $expectedLocale,
                        ),
                        'level' => 'WARNING',
                    ];
                }
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

    /**
     * Validates a document against the XLIFF schema.
     *
     * Symfony's XliffUtils::validateSchema() re-reads and re-prepares the ~105 KB
     * XSD on every call. We cache the prepared schema source per version so this
     * work happens once per process; only the (uncacheable) libxml compilation
     * runs per file. Any failure to obtain the cached schema falls back to the
     * unmodified Symfony behaviour, preserving correctness.
     *
     * @return array<int, array<string, mixed>>
     */
    private function validateSchema(DOMDocument $dom): array
    {
        $schemaSource = $this->getCachedSchemaSource($dom);
        if (null === $schemaSource) {
            return XliffUtils::validateSchema($dom);
        }

        $internalErrors = libxml_use_internal_errors(true);

        if (!@$dom->schemaValidateSource($schemaSource)) {
            $errors = [];
            foreach (libxml_get_errors() as $error) {
                $errors[] = [
                    'level' => \LIBXML_ERR_WARNING === $error->level ? 'WARNING' : 'ERROR',
                    'code' => $error->code,
                    'message' => trim($error->message),
                    'file' => $error->file ?: 'n/a',
                    'line' => $error->line,
                    'column' => $error->column,
                ];
            }
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);

            return $errors;
        }

        $dom->normalizeDocument();
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return [];
    }

    /**
     * Returns the prepared schema source for the document's XLIFF version, or
     * null when it cannot be resolved (e.g. unsupported version or a change in
     * Symfony internals), in which case the caller falls back to Symfony.
     */
    private function getCachedSchemaSource(DOMDocument $dom): ?string
    {
        try {
            $version = XliffUtils::getVersionNumber($dom);
            // @codeCoverageIgnoreStart
        } catch (Throwable) {
            return null;
        }
        // @codeCoverageIgnoreEnd

        if (isset(self::$schemaSourceCache[$version])) {
            return self::$schemaSourceCache[$version];
        }

        try {
            self::$getSchemaMethod ??= new ReflectionMethod(XliffUtils::class, 'getSchema');
            $schemaSource = self::$getSchemaMethod->invoke(null, $version);
        } catch (Throwable) {
            return null;
        }

        // @codeCoverageIgnoreStart
        if (!is_string($schemaSource)) {
            return null;
        }
        // @codeCoverageIgnoreEnd

        return self::$schemaSourceCache[$version] = $schemaSource;
    }
}
