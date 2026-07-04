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

namespace MoveElevator\ComposerTranslationValidator\Parser;

use PhpParser\Node\Expr\{Array_, ConstFetch, UnaryMinus};
use PhpParser\Node\Scalar\{Float_, Int_, String_};
use PhpParser\Node\Stmt\Return_;
use PhpParser\{Node, NodeFinder, ParserFactory};
use RuntimeException;
use Throwable;

use function array_key_exists;
use function dirname;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function sprintf;
use function strtolower;

/**
 * PhpParser.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class PhpParser extends AbstractParser implements ParserInterface
{
    /** @var array<int|string, mixed> */
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
        $source = file_get_contents($this->filePath);
        // @codeCoverageIgnoreStart
        if (false === $source) {
            throw new RuntimeException('Failed to read PHP translation file');
        }
        // @codeCoverageIgnoreEnd

        // Parse the file into an AST instead of executing it via include.
        // This prevents arbitrary code execution when validating untrusted
        // translation files (e.g. from third-party packages or pull requests).
        $ast = (new ParserFactory())->createForHostVersion()->parse($source);
        // @codeCoverageIgnoreStart
        if (null === $ast) {
            throw new RuntimeException('PHP translation file could not be parsed');
        }
        // @codeCoverageIgnoreEnd

        $return = (new NodeFinder())->findFirstInstanceOf($ast, Return_::class);
        if (!$return instanceof Return_ || !$return->expr instanceof Array_) {
            throw new RuntimeException('PHP translation file must return an array');
        }

        $this->translations = $this->evaluateArray($return->expr);
    }

    /**
     * Safely evaluates an array literal node into a PHP array.
     *
     * Only scalar literals (string, int, float, bool, null) and nested arrays
     * are supported. Any other expression (function calls, variables, constants)
     * is rejected to guarantee no code is executed.
     *
     * @return array<int|string, mixed>
     */
    private function evaluateArray(Array_ $array): array
    {
        $result = [];
        foreach ($array->items as $item) {
            if (null === $item->key) {
                throw new RuntimeException('PHP translation file must use string or integer keys');
            }

            $key = $this->evaluateScalar($item->key);
            if (!is_string($key) && !is_int($key)) {
                throw new RuntimeException('PHP translation file must use string or integer keys');
            }

            $result[$key] = $item->value instanceof Array_
                ? $this->evaluateArray($item->value)
                : $this->evaluateScalar($item->value);
        }

        return $result;
    }

    private function evaluateScalar(Node $node): string|int|float|bool|null
    {
        if ($node instanceof String_) {
            return $node->value;
        }

        if ($node instanceof Int_) {
            return $node->value;
        }

        if ($node instanceof Float_) {
            return $node->value;
        }

        if ($node instanceof UnaryMinus) {
            $operand = $this->evaluateScalar($node->expr);
            if (is_int($operand) || is_float($operand)) {
                return -$operand;
            }
            throw new RuntimeException('PHP translation file contains an unsupported expression');
        }

        if ($node instanceof ConstFetch) {
            $name = strtolower($node->name->toString());
            if ('true' === $name) {
                return true;
            }
            if ('false' === $name) {
                return false;
            }
            if ('null' === $name) {
                return null;
            }

            throw new RuntimeException('PHP translation file contains an unsupported constant');
        }

        throw new RuntimeException('PHP translation file contains an unsupported expression');
    }
}
