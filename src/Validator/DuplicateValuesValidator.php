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

use MoveElevator\ComposerTranslationValidator\Parser\{JsonParser, ParserInterface, PhpParser, XliffParser, YamlParser};
use MoveElevator\ComposerTranslationValidator\Result\Issue;

use function count;
use function is_array;
use function is_string;

/**
 * DuplicateValuesValidator.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class DuplicateValuesValidator extends AbstractValidator implements ValidatorInterface
{
    /** @var array<string, array<string, array<int, string>>> */
    protected array $valuesArray = [];

    /**
     * @return array<string, int>
     */
    public function processFile(ParserInterface $file): array
    {
        $keys = $file->extractKeys();

        if (!$keys) {
            $this->logger?->error('The source file '.$file->getFileName().' is not valid.');

            return [];
        }

        foreach ($keys as $key) {
            $value = $file->getContentByKey($key);
            if (null === $value) {
                continue;
            }
            $fileKey = !empty($this->currentFilePath) ? $this->currentFilePath : $file->getFileName();
            $this->valuesArray[$fileKey][$value][] = $key;
        }

        return [];
    }

    public function postProcess(): void
    {
        foreach ($this->valuesArray as $file => $valueKeyArray) {
            $duplicates = [];
            foreach ($valueKeyArray as $value => $keys) {
                if (count(array_unique($keys)) > 1) {
                    $duplicates[$value] = array_unique($keys);
                }
            }
            if (!empty($duplicates)) {
                $this->addIssue(new Issue(
                    $file,
                    $duplicates,
                    '',
                    $this->getShortName(),
                ));
            }
        }
    }

    public function formatIssueMessage(Issue $issue, string $prefix = ''): string
    {
        $details = $issue->getDetails();
        $resultType = $this->resultTypeOnValidationFailure();

        $level = $resultType->toString();
        $color = $resultType->toColorString();

        $messages = [];
        foreach ($details as $value => $keys) {
            if (is_string($value) && is_array($keys)) {
                $keyList = implode('`, `', $keys);
                $messages[] = "- <fg=$color>$level</> {$prefix}the translation value "
                    ."`$value` occurs in multiple keys (`$keyList`)";
            }
        }

        return implode("\n", $messages);
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

    protected function resetState(): void
    {
        parent::resetState();
        $this->valuesArray = [];
    }
}
