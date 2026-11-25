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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validator\{DuplicateKeysValidator, DuplicateValuesValidator, EmptyValuesValidator, HtmlTagValidator, KeyNamingConventionValidator, MismatchValidator, PlaceholderConsistencyValidator, ValidatorInterface, XliffSchemaValidator};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * MessageSanitizationTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class MessageSanitizationTest extends TestCase
{
    private const HOSTILE = "k1\n::error file=x::PWNED<bg=red>RED</>\e[2J<href=https://evil.example>x</>";

    /**
     * @return iterable<string, array{ValidatorInterface, array<mixed>}>
     */
    public static function hostileIssueProvider(): iterable
    {
        $h = self::HOSTILE;

        yield 'mismatch' => [new MismatchValidator(), [
            'key' => $h,
            'files' => [
                ['file' => '/p/a.en.json', 'value' => 'x'],
                ['file' => "/p/{$h}.de.json", 'value' => null],
            ],
        ]];
        yield 'duplicate keys' => [new DuplicateKeysValidator(), [$h => 2]];
        yield 'duplicate values' => [new DuplicateValuesValidator(), [$h => [$h, 'other']]];
        yield 'empty values' => [new EmptyValuesValidator(), [$h => '']];
        yield 'placeholder' => [new PlaceholderConsistencyValidator(), [
            'key' => $h,
            'inconsistencies' => ["File '{$h}' is missing placeholders: %s"],
        ]];
        yield 'html tag' => [new HtmlTagValidator(), [
            'key' => $h,
            'inconsistencies' => ["File '{$h}' has different class attribute"],
        ]];
        yield 'key naming convention' => [new KeyNamingConventionValidator(), [
            'key' => $h,
            'expected_convention' => 'snake_case',
            'suggestion' => $h.'_x',
        ]];
        yield 'xliff schema' => [new XliffSchemaValidator(), ['message' => $h]];
    }

    /**
     * @param array<mixed> $details
     */
    #[DataProvider('hostileIssueProvider')]
    public function testUntrustedValuesCannotInjectOutput(ValidatorInterface $validator, array $details): void
    {
        $message = $validator->formatIssueMessage(new Issue('/p/a.en.json', $details, 'P', 'V'));

        foreach (explode("\n", $message) as $line) {
            $this->assertFalse(str_starts_with(ltrim($line), '::'), 'Line starts with a workflow command: '.$line);
        }

        $rendered = (new OutputFormatter(true))->format($message);

        $this->assertNotNull($rendered);
        $this->assertStringNotContainsString("\e[2J", $rendered);
        $this->assertStringNotContainsString("\e[41m", $rendered);
        $this->assertStringNotContainsString("\e]8;", $rendered);
        $this->assertStringContainsString('<bg=red>RED</>', $rendered);
    }
}
