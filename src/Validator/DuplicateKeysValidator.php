<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;

class DuplicateKeysValidator extends AbstractValidator implements ValidatorInterface
{
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

        $duplicateKeys = array_filter(array_count_values($keys), static fn ($count) => $count > 1);
        if (!empty($duplicateKeys)) {
            return $duplicateKeys;
        }

        return [];
    }

    public function formatIssueMessage(Issue $issue, string $prefix = '', bool $isVerbose = false): string
    {
        $details = $issue->getDetails();
        $resultType = $this->resultTypeOnValidationFailure();

        $level = $resultType->toString();
        $color = $resultType->toColorString();

        $messages = [];
        foreach ($details as $key => $count) {
            if (is_string($key) && is_int($count)) {
                $messages[] = "- <fg=$color>$level</> {$prefix}the translation key `$key` occurs multiple times ({$count}x)";
            }
        }

        return implode("\n", $messages);
    }

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array
    {
        return [XliffParser::class];
    }
}
