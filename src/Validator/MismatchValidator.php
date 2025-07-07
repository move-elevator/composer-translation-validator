<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MismatchValidator extends AbstractValidator implements ValidatorInterface
{
    /** @var array<string, array<string>> */
    protected array $keyArray = [];

    public function processFile(ParserInterface $file): array
    {
        $keys = $file->extractKeys();

        if (!$keys) {
            $this->logger?->error('The source file '.$file->getFileName().' is not valid.');

            return [];
        }
        foreach ($keys as $key) {
            $value = $file->getContentByKey($key);
            $this->keyArray[$file->getFileName()][$key] = $value ?? null;
        }

        return [];
    }

    public function postProcess(): void
    {
        $allKeys = [];
        foreach ($this->keyArray as $values) {
            $allKeys[] = array_keys($values);
        }
        $allKeys = array_unique(array_merge(...$allKeys));

        foreach ($allKeys as $key) {
            $missingInSome = false;
            foreach ($this->keyArray as $keys) {
                if (!array_key_exists($key, $keys)) {
                    $missingInSome = true;
                    break;
                }
            }
            if ($missingInSome) {
                $result = [
                    'key' => $key,
                    'files' => [],
                ];
                foreach ($this->keyArray as $file => $keys) {
                    $result['files'][] = [
                        'file' => $file,
                        'value' => $keys[$key] ?? null,
                    ];
                }
                $this->addIssue(new Issue(
                    '',
                    $result,
                    '',
                    'MismatchValidator'
                ));
            }
        }
    }

    /**
     * @param array<string, array<int, array<mixed>>> $issueSets
     */
    public function renderIssueSets(InputInterface $input, OutputInterface $output, array $issueSets): void
    {
        $rows = [];
        $header = ['Key'];
        $allFiles = [];

        foreach ($issueSets as $issuesPerFile) {
            foreach ($issuesPerFile as $issues) {
                // Handle both new format (with 'issues' key) and old format (direct data)
                if (isset($issues['issues']) && is_array($issues['issues'])) {
                    $issueData = $issues['issues'];
                } else {
                    $issueData = $issues;
                }
                $key = $issueData['key'];
                $files = $issueData['files'];
                if (empty($allFiles)) {
                    $allFiles = array_column($files, 'file');
                    $header = array_merge(['Key'], array_map(static fn ($f) => "<fg=red>$f</>", $allFiles));
                }
                $row = [$key];
                foreach ($files as $fileInfo) {
                    $row[] = $fileInfo['value'] ?? '<fg=yellow><missing></>';
                }
                $rows[] = $row;
            }
        }

        (new Table($output))
            ->setHeaders($header)
            ->setRows($rows)
            ->setStyle(
                (new TableStyle())
                    ->setCellHeaderFormat('%s')
            )
            ->render();
    }

    public function explain(): string
    {
        return 'This validator checks for keys that are present in some files but not in others. '
            .'It helps to identify mismatches in translation keys across different translation files.';
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
        $this->keyArray = [];
    }
}
