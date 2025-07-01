<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

    /**
     * @param array<string, array<int, array{
     *      file: string,
     *      issues: array<string, int>,
     *      parser: string,
     *      type: string
     *  }>> $issueSets
     */
    public function renderIssueSets(InputInterface $input, OutputInterface $output, array $issueSets): void
    {
        $rows = [];
        $currentFile = null;

        foreach ($issueSets as $issues) {
            foreach ($issues as $duplicates) {
                if ($currentFile !== $duplicates['file'] && null !== $currentFile) {
                    $rows[] = new TableSeparator();
                }
                $currentFile = $duplicates['file'];
                foreach ($duplicates['issues'] as $key => $count) {
                    $rows[] = ['<fg=red>'.$duplicates['file'].'</>', $key, $count];
                    $duplicates['file'] = ''; // Reset file for subsequent rows
                }
            }
        }

        (new Table($output))
            ->setHeaders(['File', 'Key', 'Count duplicates'])
            ->setRows($rows)
            ->setStyle(
                (new TableStyle())
                    ->setCellHeaderFormat('%s')
            )
            ->render();
    }

    public function explain(): string
    {
        return 'This validator checks for duplicate keys in translation files. '
            .'If a key appears more than once in a file, it will be reported as an issue.';
    }

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array
    {
        return [XliffParser::class];
    }
}
