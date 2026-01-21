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

use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;
use MoveElevator\ComposerTranslationValidator\Parser\{JsonParser, ParserInterface, PhpParser, XliffParser, YamlParser};
use Psr\Log\LoggerInterface;

use function count;
use function sprintf;

/**
 * KeyDepthValidator.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
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
