<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;

class EmptyValuesValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * @return array<string, string>
     */
    public function processFile(ParserInterface $file): array
    {
        $keys = $file->extractKeys();

        if (null === $keys) {
            $this->logger?->error(
                'The source file '.$file->getFileName().' is not valid.'
            );

            return [];
        }

        $emptyValues = [];
        foreach ($keys as $key) {
            $value = $file->getContentByKey($key);

            // Check if value is empty (null, empty string, or only whitespace)
            if (null === $value || '' === trim($value)) {
                $emptyValues[$key] = $value ?? '';
            }
        }

        return $emptyValues;
    }

    public function formatIssueMessage(Issue $issue, string $prefix = ''): string
    {
        $details = $issue->getDetails();
        $resultType = $this->resultTypeOnValidationFailure();

        $level = $resultType->toString();
        $color = $resultType->toColorString();

        $messages = [];
        foreach ($details as $key => $value) {
            if (is_string($key)) {
                $valueDescription = '' === $value ? 'empty' : 'whitespace only';
                $messages[] = "- <fg=$color>$level</> {$prefix}the translation key "
                    ."`$key` has an $valueDescription value";
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

    public function resultTypeOnValidationFailure(): ResultType
    {
        return ResultType::WARNING;
    }
}
