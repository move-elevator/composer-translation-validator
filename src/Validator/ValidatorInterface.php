<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface ValidatorInterface
{
    /**
     * @return array<string, mixed>
     */
    public function processFile(ParserInterface $file): array;

    /**
     * @param array<string, mixed> $issueSets
     */
    public function renderIssueSets(InputInterface $input, OutputInterface $output, array $issueSets): void;

    public function explain(): string;
}
