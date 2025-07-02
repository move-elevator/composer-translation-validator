<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
                $this->issues[] = [
                    'file' => $file,
                    'issues' => $duplicates,
                ];
            }
        }
    }

    /**
     * @param array<string, array<int, array{
     *      file: string,
     *      issues: array<string, array<int, string>>,
     *  }>> $issueSets
     */
    public function renderIssueSets(InputInterface $input, OutputInterface $output, array $issueSets): void
    {
        $rows = [];
        $currentFile = null;

        foreach ($issueSets as $duplicate) {
            if ($currentFile !== $duplicate['file'] && null !== $currentFile) {
                $rows[] = new TableSeparator();
            }
            $currentFile = $duplicate['file'];
            foreach ($duplicate['issues'] as $value => $keys) {
                $rows[] = [
                    '<fg=red>'.$duplicate['file'].'</>',
                    implode("\n", $keys),
                    '<fg=yellow>'.$value.'</>',
                ];
                $duplicate['file'] = '';
            }
        }

        (new Table($output))
            ->setHeaders(['File', 'Key', 'Value'])
            ->setRows($rows)
            ->setStyle(
                (new TableStyle())
                    ->setCellHeaderFormat('%s')
            )
            ->render();
    }

    public function explain(): string
    {
        return 'This validator checks for duplicate values in translation files. '
            .'If a value is assigned to multiple keys in a file, it will be reported as an issue.';
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
