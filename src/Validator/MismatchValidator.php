<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use Symfony\Component\Console\Helper\Table;
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
        $this->keyArray[$file->getFileName()] = $keys;

        return [];
    }

    public function postProcess(): void
    {
        $allKeys = [];
        foreach ($this->keyArray as $values) {
            $allKeys[] = array_values($values);
        }
        $allKeys = array_unique(array_merge(...$allKeys));

        foreach ($allKeys as $key) {
            $missingInSome = false;
            foreach ($this->keyArray as $keys) {
                if (!in_array($key, $keys, true)) {
                    $missingInSome = true;
                    break;
                }
            }
            if ($missingInSome) {
                foreach ($this->keyArray as $file => $keys) {
                    $this->issues[$key][$file] = in_array($key, $keys, true)
                        ? $keys[array_search($key, $keys, true)]
                        : null;
                }
            }
        }
    }

    /**
     * @param array<array<string, array<string, string|null>>> $issueSets
     */
    public function renderIssueSets(InputInterface $input, OutputInterface $output, array $issueSets): void
    {
        foreach ($issueSets as $issues) {
            $first = reset($issues);
            $allFiles = array_keys($first);
            $header = array_merge(['Key'], $allFiles);

            $rows = [];
            foreach ($issues as $key => $files) {
                $row = [$key];
                foreach ($allFiles as $file) {
                    $row[] = array_key_exists($file, $files) && null !== $files[$file] ? $files[$file] : '<fg=yellow>–</>';
                }
                $rows[] = $row;
            }

            (new Table($output))
                ->setHeaders($header)
                ->setRows($rows)
                ->setStyle('markdown')
                ->render();
        }
    }

    public function explain(): string
    {
        return 'This validator checks for keys that are present in some files but not in others. '
            .'It helps to identify mismatches in translation keys across different translation files.';
    }
}
