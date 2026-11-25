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

namespace MoveElevator\ComposerTranslationValidator\Tests\Config;

use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;
use MoveElevator\ComposerTranslationValidator\Validator\{DuplicateValuesValidator, KeyNamingConventionValidator};
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};
use PHPUnit\Framework\TestCase;

/**
 * TranslationValidatorConfigTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
#[CoversClass(TranslationValidatorConfig::class)]
final class TranslationValidatorConfigTest extends TestCase
{
    private TranslationValidatorConfig $config;

    protected function setUp(): void
    {
        $this->config = new TranslationValidatorConfig();
    }

    /**
     * @param array<int, string>|bool|string $value
     */
    #[DataProvider('setterGetterProvider')]
    public function testSetterAndGetter(string $setter, string $getter, array|bool|string $value): void
    {
        $this->config->{$setter}($value);
        self::assertSame($value, $this->config->{$getter}());
    }

    /**
     * @return iterable<string, array{string, string, array<int, string>|bool|string}>
     */
    public static function setterGetterProvider(): iterable
    {
        yield 'paths' => ['setPaths', 'getPaths', ['path1', 'path2']];
        yield 'validators' => ['setValidators', 'getValidators', ['Validator1', 'Validator2']];
        yield 'file-detectors' => ['setFileDetectors', 'getFileDetectors', ['Detector1', 'Detector2']];
        yield 'parsers' => ['setParsers', 'getParsers', ['Parser1', 'Parser2']];
        yield 'only' => ['setOnly', 'getOnly', ['Only1', 'Only2']];
        yield 'skip' => ['setSkip', 'getSkip', ['Skip1', 'Skip2']];
        yield 'exclude' => ['setExclude', 'getExclude', ['vendor/*', 'node_modules/*']];
        yield 'strict true' => ['setStrict', 'getStrict', true];
        yield 'strict false' => ['setStrict', 'getStrict', false];
        yield 'dry-run true' => ['setDryRun', 'getDryRun', true];
        yield 'dry-run false' => ['setDryRun', 'getDryRun', false];
        yield 'format' => ['setFormat', 'getFormat', 'json'];
        yield 'verbose true' => ['setVerbose', 'getVerbose', true];
        yield 'verbose false' => ['setVerbose', 'getVerbose', false];
    }

    /**
     * @param array<int, string> $expected
     */
    #[DataProvider('adderProvider')]
    public function testAdder(string $adder, string $getter, string $value, array $expected): void
    {
        $this->config->{$adder}($value);
        self::assertSame($expected, $this->config->{$getter}());
    }

    /**
     * @return iterable<string, array{string, string, string, array<int, string>}>
     */
    public static function adderProvider(): iterable
    {
        yield 'addValidator' => ['addValidator', 'getValidators', 'SomeValidator', ['SomeValidator']];
        yield 'addFileDetector' => ['addFileDetector', 'getFileDetectors', 'SomeFileDetector', ['SomeFileDetector']];
        yield 'addParser' => ['addParser', 'getParsers', 'SomeParser', ['SomeParser']];
        yield 'only' => ['only', 'getOnly', 'OnlyValidator', ['OnlyValidator']];
        yield 'skip' => ['skip', 'getSkip', 'SkipValidator', [DuplicateValuesValidator::class, KeyNamingConventionValidator::class, 'SkipValidator']];
    }

    public function testDefaultValues(): void
    {
        self::assertSame([], $this->config->getPaths());
        self::assertSame([], $this->config->getValidators());
        self::assertSame([], $this->config->getFileDetectors());
        self::assertSame([], $this->config->getParsers());
        self::assertSame([], $this->config->getOnly());
        self::assertSame([DuplicateValuesValidator::class, KeyNamingConventionValidator::class], $this->config->getSkip());
        self::assertSame([], $this->config->getExclude());
        self::assertFalse($this->config->getStrict());
        self::assertFalse($this->config->getDryRun());
        self::assertSame('cli', $this->config->getFormat());
        self::assertFalse($this->config->getVerbose());
    }

    /**
     * @param array<string, mixed> $expected
     */
    #[DataProvider('toArrayProvider')]
    public function testToArray(bool $configured, array $expected): void
    {
        if ($configured) {
            $this->config
                ->setPaths(['path1', 'path2'])
                ->setValidators(['Validator1'])
                ->setFileDetectors(['Detector1'])
                ->setParsers(['Parser1'])
                ->setOnly(['Only1'])
                ->setSkip(['Skip1'])
                ->setExclude(['vendor/*'])
                ->setStrict(true)
                ->setDryRun(true)
                ->setFormat('json')
                ->setVerbose(true);
        }

        self::assertSame($expected, $this->config->toArray());
    }

    /**
     * @return iterable<string, array{bool, array<string, mixed>}>
     */
    public static function toArrayProvider(): iterable
    {
        yield 'all values' => [true, [
            'paths' => ['path1', 'path2'],
            'validators' => ['Validator1'],
            'file-detectors' => ['Detector1'],
            'parsers' => ['Parser1'],
            'only' => ['Only1'],
            'skip' => ['Skip1'],
            'exclude' => ['vendor/*'],
            'strict' => true,
            'dry-run' => true,
            'format' => 'json',
            'verbose' => true,
            'validator-settings' => [],
        ]];

        yield 'default values' => [false, [
            'paths' => [],
            'validators' => [],
            'file-detectors' => [],
            'parsers' => [],
            'only' => [],
            'skip' => [DuplicateValuesValidator::class, KeyNamingConventionValidator::class],
            'exclude' => [],
            'strict' => false,
            'dry-run' => false,
            'format' => 'cli',
            'verbose' => false,
            'validator-settings' => [],
        ]];
    }

    public function testFluentInterface(): void
    {
        $result = $this->config
            ->setPaths(['test'])
            ->addValidator('Validator1')
            ->addFileDetector('Detector1')
            ->addParser('Parser1')
            ->only('Only1')
            ->skip('Skip1')
            ->setValidators(['test'])
            ->setFileDetectors(['test'])
            ->setParsers(['test'])
            ->setOnly(['test'])
            ->setSkip(['test'])
            ->setExclude(['test'])
            ->setStrict(true)
            ->setDryRun(true)
            ->setFormat('json')
            ->setVerbose(true);

        self::assertSame($this->config, $result);
        self::assertSame(['test'], $this->config->getPaths());
        self::assertSame(['test'], $this->config->getValidators());
        self::assertTrue($this->config->getStrict());
        self::assertSame('json', $this->config->getFormat());
    }
}
