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

use MoveElevator\ComposerTranslationValidator\Config\ConfigFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * ConfigFactoryTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ConfigFactoryTest extends TestCase
{
    private ConfigFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ConfigFactory();
    }

    public function testCreateFromArrayWithAllOptions(): void
    {
        $data = [
            'paths' => ['translations/', 'locale/'],
            'validators' => ['Validator1', 'Validator2'],
            'file-detectors' => ['Detector1'],
            'parsers' => ['Parser1'],
            'only' => ['OnlyValidator'],
            'skip' => ['SkipValidator'],
            'exclude' => ['*.backup', '*.tmp'],
            'strict' => true,
            'dry-run' => false,
            'format' => 'json',
            'verbose' => true,
        ];

        $config = $this->factory->createFromArray($data);

        self::assertSame(['translations/', 'locale/'], $config->getPaths());
        self::assertSame(['Validator1', 'Validator2'], $config->getValidators());
        self::assertSame(['Detector1'], $config->getFileDetectors());
        self::assertSame(['Parser1'], $config->getParsers());
        self::assertSame(['OnlyValidator'], $config->getOnly());
        self::assertSame(['SkipValidator'], $config->getSkip());
        self::assertSame(['*.backup', '*.tmp'], $config->getExclude());
        self::assertTrue($config->getStrict());
        self::assertFalse($config->getDryRun());
        self::assertSame('json', $config->getFormat());
        self::assertTrue($config->getVerbose());
    }

    public function testCreateFromArrayWithEmptyData(): void
    {
        // Use minimal valid data to avoid schema validation issues
        $config = $this->factory->createFromArray(['paths' => ['translations/']]);

        self::assertSame(['translations/'], $config->getPaths());
        self::assertSame([], $config->getValidators());
        self::assertSame([], $config->getFileDetectors());
        self::assertSame([], $config->getParsers());
        self::assertSame([], $config->getOnly());
        self::assertSame([
            \MoveElevator\ComposerTranslationValidator\Validator\DuplicateValuesValidator::class,
            \MoveElevator\ComposerTranslationValidator\Validator\KeyNamingConventionValidator::class,
        ], $config->getSkip());
        self::assertSame([], $config->getExclude());
        self::assertFalse($config->getStrict());
        self::assertFalse($config->getDryRun());
        self::assertSame('cli', $config->getFormat());
        self::assertFalse($config->getVerbose());
    }

    public function testCreateFromArrayWithPartialData(): void
    {
        $data = [
            'paths' => ['translations/'],
            'strict' => true,
            'format' => 'cli',
        ];

        $config = $this->factory->createFromArray($data);

        self::assertSame(['translations/'], $config->getPaths());
        self::assertTrue($config->getStrict());
        self::assertSame('cli', $config->getFormat());
        self::assertSame([], $config->getValidators());
        self::assertFalse($config->getDryRun());
    }

    public function testCreateFromArrayValidatesData(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed');

        $this->factory->createFromArray(['paths' => 'invalid']);
    }

    public function testCreateFromArrayValidatesFormatValue(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed');

        $this->factory->createFromArray(['format' => 'invalid']);
    }
}
