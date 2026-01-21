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
 * KeyCountValidator.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class KeyCountValidator extends AbstractValidator implements ValidatorInterface
{
    private int $threshold = 300;

    public function __construct(?LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    public function setConfig(?TranslationValidatorConfig $config): void
    {
        if ($config && $config->hasValidatorSettings('KeyCountValidator')) {
            $settings = $config->getValidatorSettings('KeyCountValidator');
            $threshold = $settings['threshold'] ?? 300;

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

        $keyCount = count($keys);

        if ($keyCount > $this->threshold) {
            return [
                'message' => sprintf(
                    'File contains %d translation keys, which exceeds the threshold of %d keys',
                    $keyCount,
                    $this->threshold,
                ),
                'key_count' => $keyCount,
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
}
