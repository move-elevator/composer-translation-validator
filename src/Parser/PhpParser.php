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

use RuntimeException;
use Throwable;

use function array_key_exists;
use function dirname;
use function is_array;
use function is_string;
use function sprintf;

/**
 * PhpParser.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class PhpParser extends AbstractParser implements ParserInterface
{
    /** @var array<string, mixed> */
    private array $translations = [];

    public function __construct(string $filePath)
    {
        parent::__construct($filePath);

        try {
            $this->loadTranslations();
        } catch (Throwable $e) {
            throw new RuntimeException(sprintf('Failed to parse PHP file "%s": %s', $filePath, $e->getMessage()), 0, $e);
        }
    }

    /**
     * @return array<int, string>|null
     */
    public function extractKeys(): ?array
    {
        if (empty($this->translations)) {
            return [];
        }

        $extract = static function (
            array $data,
            string $prefix = '',
        ) use (&$extract): array {
            $keys = [];
            foreach ($data as $key => $value) {
                $fullKey = '' === $prefix
                    ? $key
                    : $prefix.'.'.$key;
                if (is_array($value)) {
                    $extracted = $extract($value, $fullKey);
                    foreach ($extracted as $k) {
                        $keys[] = $k;
                    }
                } else {
                    $keys[] = $fullKey;
                }
            }

            return $keys;
        };

        return $extract($this->translations);
    }

    public function getContentByKey(string $key): ?string
    {
        // Note: the $attribute parameter is required by ParserInterface
        // but is not used for PHP files, since PHP has no source/target concept.
        $parts = explode('.', $key);
        $value = $this->translations;

        foreach ($parts as $part) {
            if (is_array($value) && array_key_exists($part, $value)) {
                $value = $value[$part];
            } else {
                return null;
            }
        }

        return is_string($value) ? $value : null;
    }

    /**
     * @return array<int, string>
     */
    public static function getSupportedFileExtensions(): array
    {
        return ['php'];
    }

    public function getLanguage(): string
    {
        $fileName = $this->getFileName();

        // Laravel pattern: en/messages.php, de/auth.php
        $directory = basename(dirname($this->filePath));
        if (preg_match('/^[a-z]{2}(?:[-_][A-Z]{2})?$/', $directory)) {
            return $directory;
        }

        // Symfony pattern: messages.en.php, validators.de.php
        if (preg_match('/\.([a-z]{2}(?:[-_][A-Z]{2})?)\.php$/i', $fileName, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function loadTranslations(): void
    {
        // Temporarily disable error reporting to prevent issues with include
        $errorReporting = error_reporting(0);

        try {
            // Use output buffering to catch any output from the included file
            ob_start();
            $result = include $this->filePath;
            ob_end_clean();

            if (!is_array($result)) {
                throw new RuntimeException('PHP translation file must return an array');
            }

            $this->translations = $result;
        } finally {
            // Restore error reporting
            error_reporting($errorReporting);
        }
    }
}
