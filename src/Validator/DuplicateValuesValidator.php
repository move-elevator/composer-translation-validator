<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;

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
            $this->valuesArray[$file->getFileName()][$value][] = $key;
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
                    'DuplicateValuesValidator'
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
                $messages[] = "- <fg=$color>$level</> {$prefix}the translation value `$value` occurs in multiple keys (`$keyList`)";
            }
        }

        return implode("\n", $messages);
    }

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array
    {
        return [XliffParser::class, YamlParser::class];
    }

    protected function resetState(): void
    {
        parent::resetState();
        $this->valuesArray = [];
    }

    public function resultTypeOnValidationFailure(): ResultType
    {
        return ResultType::WARNING;
    }
}
