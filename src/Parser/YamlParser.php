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

use Exception;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

use function is_string;
use function sprintf;

/**
 * YamlParser.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class YamlParser extends AbstractParser implements ParserInterface
{
    /** @var array<string, mixed> */
    private array $yaml = [];

    public function __construct(string $filePath)
    {
        parent::__construct($filePath);

        try {
            $parsed = Yaml::parseFile($filePath);
        } catch (Exception $e) {
            throw new RuntimeException(sprintf('Failed to parse YAML file "%s": %s', $filePath, $e->getMessage()), 0, $e);
        }

        if (null !== $parsed && !is_array($parsed)) {
            throw new RuntimeException(sprintf('Failed to parse YAML file "%s": expected a mapping, got %s.', $filePath, get_debug_type($parsed)));
        }

        $this->yaml = $parsed ?? [];
    }

    /**
     * @return array<int, string>|null
     */
    public function extractKeys(): ?array
    {
        return $this->flattened($this->yaml)['keys'];
    }

    public function getContentByKey(string $key): ?string
    {
        // Note: the $attribute parameter is required by ParserInterface
        // but is not used for YAML, since YAML has no source/target concept.
        $value = $this->flattened($this->yaml)['values'][$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @return array<int, string>
     */
    public static function getSupportedFileExtensions(): array
    {
        return ['yaml', 'yml'];
    }

    public function getLanguage(): string
    {
        if (preg_match(
            '/\.(\w{2})\./',
            $this->getFileName(),
            $matches,
        )) {
            return $matches[1];
        }

        return '';
    }
}
