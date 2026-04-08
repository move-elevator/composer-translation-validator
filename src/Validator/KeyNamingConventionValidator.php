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

use InvalidArgumentException;
use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;
use MoveElevator\ComposerTranslationValidator\Enum\KeyNamingConvention;
use MoveElevator\ComposerTranslationValidator\Parser\{JsonParser, ParserInterface, PhpParser, XliffParser, YamlParser};
use MoveElevator\ComposerTranslationValidator\Result\Issue;

use function is_string;

/**
 * KeyNamingConventionValidator.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class KeyNamingConventionValidator extends AbstractValidator implements ValidatorInterface
{
    private ?KeyNamingConvention $convention = null;
    private ?string $customPattern = null;
    private ?TranslationValidatorConfig $config = null;
    private bool $configHintShown = false;
    private readonly KeyConverter $keyConverter;
    private readonly ConventionDetector $conventionDetector;

    public function __construct(?\Psr\Log\LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->keyConverter = new KeyConverter();
        $this->conventionDetector = new ConventionDetector();
    }

    public function setConfig(?TranslationValidatorConfig $config): void
    {
        $this->config = $config;
        $this->loadConventionFromConfig();
    }

    public function processFile(ParserInterface $file): array
    {
        // Reset hint shown flag for each new file
        $this->configHintShown = false;

        $keys = $file->extractKeys();

        if (null === $keys) {
            $this->logger?->error(
                'The source file '.$file->getFileName().' is not valid.',
            );

            return [];
        }

        // If no convention is configured, analyze keys for inconsistencies
        if (null === $this->convention && null === $this->customPattern) {
            $issueData = $this->conventionDetector->analyzeKeyConsistency($keys, $file->getFileName());
        } else {
            // Use configured convention
            $issueData = [];
            foreach ($keys as $key) {
                if (!$this->validateKeyFormat($key)) {
                    $issueData[] = [
                        'key' => $key,
                        'file' => $file->getFileName(),
                        'expected_convention' => $this->convention->value ?? 'custom pattern',
                        'pattern' => $this->getActivePattern(),
                        'suggestion' => $this->suggestCorrection($key),
                    ];
                }
            }
        }

        return $issueData;
    }

    public function setConvention(string $convention): void
    {
        if ('dot.notation' === $convention) {
            throw new InvalidArgumentException('dot.notation cannot be configured explicitly. It is used internally for detection but should not be set as a configuration option.');
        }

        $this->convention = KeyNamingConvention::fromString($convention);
    }

    public function setCustomPattern(string $pattern): void
    {
        $result = @preg_match($pattern, '');
        if (false === $result) {
            throw new InvalidArgumentException('Invalid regex pattern provided');
        }

        $this->customPattern = $pattern;
        $this->convention = null; // Custom pattern overrides convention
    }

    public function formatIssueMessage(Issue $issue, string $prefix = ''): string
    {
        $details = $issue->getDetails();
        $resultType = $this->resultTypeOnValidationFailure();

        $level = $resultType->toString();
        $color = $resultType->toColorString();

        $key = $details['key'] ?? 'unknown';

        // Handle different issue types
        if (isset($details['inconsistency_type']) && 'mixed_conventions' === $details['inconsistency_type']) {
            $detectedConventions = $details['detected_conventions'] ?? [];
            $dominantConvention = $details['dominant_convention'] ?? 'unknown';

            $detectedStr = implode(', ', $detectedConventions);

            $message = "key naming inconsistency: `{$key}` uses {$detectedStr} convention";
            $message .= ", but this file predominantly uses {$dominantConvention}";

            if ('unknown' !== $dominantConvention && 'mixed_conventions' !== $dominantConvention) {
                $suggestion = $this->suggestKeyConversion($key, $dominantConvention);
                if ($suggestion !== $key) {
                    $message .= ". Consider: `{$suggestion}`";
                }
            } else {
                $message .= '. Consider standardizing all keys to use the same naming convention';
            }

            if ($this->isAutoDetectionMode() && !$this->configHintShown) {
                $message .= $this->getConfigurationHint();
                $this->configHintShown = true;
            }
        } else {
            $convention = $details['expected_convention'] ?? 'custom pattern';
            $suggestion = $details['suggestion'] ?? '';

            $message = "key naming convention violation: `{$key}` does not follow the configured {$convention} convention";
            if (!empty($suggestion) && $suggestion !== $key) {
                $message .= ". Suggested: `{$suggestion}`";
            }
        }

        return "- <fg={$color}>{$level}</> {$prefix}{$message}";
    }

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array
    {
        return [XliffParser::class, YamlParser::class, JsonParser::class, PhpParser::class];
    }

    public function resultTypeOnValidationFailure(): ResultType
    {
        return ResultType::WARNING;
    }

    /**
     * Get available naming conventions.
     *
     * @return array<string, array{pattern: string, description: string}>
     */
    public static function getAvailableConventions(): array
    {
        $conventions = [];

        foreach (KeyNamingConvention::getConfigurableConventions() as $value) {
            $convention = KeyNamingConvention::from($value);
            $conventions[$value] = [
                'pattern' => $convention->getPattern(),
                'description' => $convention->getDescription(),
            ];
        }

        return $conventions;
    }

    /**
     * Check if validator should run based on configuration.
     */
    public function shouldRun(): bool
    {
        return true; // Always run, even without configuration
    }

    private function loadConventionFromConfig(): void
    {
        if (null === $this->config) {
            return;
        }

        $validatorSettings = $this->config->getValidatorSettings('KeyNamingConventionValidator');

        if (empty($validatorSettings)) {
            return;
        }

        // Load convention from config
        if (isset($validatorSettings['convention']) && is_string($validatorSettings['convention'])) {
            try {
                $this->setConvention($validatorSettings['convention']);
            } catch (InvalidArgumentException $e) {
                $this->logger?->warning(
                    'Invalid convention in config: '.$validatorSettings['convention'].'. '.$e->getMessage(),
                );
            }
        }

        // Load custom pattern from config (overrides convention)
        if (isset($validatorSettings['custom_pattern']) && is_string($validatorSettings['custom_pattern'])) {
            try {
                $this->setCustomPattern($validatorSettings['custom_pattern']);
            } catch (InvalidArgumentException $e) {
                $this->logger?->warning(
                    'Invalid custom pattern in config: '.$validatorSettings['custom_pattern'].'. '.$e->getMessage(),
                );
            }
        }
    }

    private function validateKeyFormat(string $key): bool
    {
        if (null === $this->convention && null === $this->customPattern) {
            return true; // No validation if no pattern is set
        }

        // If custom pattern is set, use it directly
        if (null !== $this->customPattern) {
            return (bool) preg_match($this->customPattern, $key);
        }

        // For base conventions, validate each segment separately if key contains dots
        if (str_contains($key, '.')) {
            $segments = explode('.', $key);
            foreach ($segments as $segment) {
                if (!$this->validateSegment($segment)) {
                    return false;
                }
            }

            return true;
        }

        // Single segment, validate directly
        return $this->validateSegment($key);
    }

    private function validateSegment(string $segment): bool
    {
        if (null === $this->convention) {
            return true;
        }

        return $this->convention->matches($segment);
    }

    private function getActivePattern(): ?string
    {
        if (null !== $this->customPattern) {
            return $this->customPattern;
        }

        return $this->convention?->getPattern();
    }

    private function suggestCorrection(string $key): string
    {
        if (null === $this->convention) {
            return $key;
        }

        return $this->keyConverter->convertKey($key, $this->convention);
    }

    /**
     * Check if the validator is in auto-detection mode (no explicit configuration).
     */
    private function isAutoDetectionMode(): bool
    {
        return null === $this->convention && null === $this->customPattern;
    }

    /**
     * Get a helpful configuration hint for users.
     */
    private function getConfigurationHint(): string
    {
        // Use only configurable conventions (excludes dot.notation)
        $availableConventions = KeyNamingConvention::getConfigurableConventions();
        $conventionsList = implode(', ', $availableConventions);

        return "\n  Tip: Configure a specific naming convention in a configuration file to avoid inconsistencies. "
            ."Available conventions: {$conventionsList}. ";
    }

    /**
     * Suggest a key conversion to match the dominant convention.
     */
    private function suggestKeyConversion(string $key, string $targetConvention): string
    {
        try {
            $convention = KeyNamingConvention::fromString($targetConvention);

            return $this->keyConverter->convertKey($key, $convention);
        } catch (InvalidArgumentException) {
            return $key;
        }
    }
}
