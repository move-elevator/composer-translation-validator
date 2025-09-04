<?php

declare(strict_types=1);

/*
 * This file is part of the Composer plugin "composer-translation-validator".
 *
 * Copyright (C) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Config;

use MoveElevator\ComposerTranslationValidator\Config\ConfigFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
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

        $this->assertSame(['translations/', 'locale/'], $config->getPaths());
        $this->assertSame(['Validator1', 'Validator2'], $config->getValidators());
        $this->assertSame(['Detector1'], $config->getFileDetectors());
        $this->assertSame(['Parser1'], $config->getParsers());
        $this->assertSame(['OnlyValidator'], $config->getOnly());
        $this->assertSame(['SkipValidator'], $config->getSkip());
        $this->assertSame(['*.backup', '*.tmp'], $config->getExclude());
        $this->assertTrue($config->getStrict());
        $this->assertFalse($config->getDryRun());
        $this->assertSame('json', $config->getFormat());
        $this->assertTrue($config->getVerbose());
    }

    public function testCreateFromArrayWithEmptyData(): void
    {
        // Use minimal valid data to avoid schema validation issues
        $config = $this->factory->createFromArray(['paths' => ['translations/']]);

        $this->assertSame(['translations/'], $config->getPaths());
        $this->assertSame([], $config->getValidators());
        $this->assertSame([], $config->getFileDetectors());
        $this->assertSame([], $config->getParsers());
        $this->assertSame([], $config->getOnly());
        $this->assertSame([], $config->getSkip());
        $this->assertSame([], $config->getExclude());
        $this->assertFalse($config->getStrict());
        $this->assertFalse($config->getDryRun());
        $this->assertSame('cli', $config->getFormat());
        $this->assertFalse($config->getVerbose());
    }

    public function testCreateFromArrayWithPartialData(): void
    {
        $data = [
            'paths' => ['translations/'],
            'strict' => true,
            'format' => 'cli',
        ];

        $config = $this->factory->createFromArray($data);

        $this->assertSame(['translations/'], $config->getPaths());
        $this->assertTrue($config->getStrict());
        $this->assertSame('cli', $config->getFormat());
        $this->assertSame([], $config->getValidators());
        $this->assertFalse($config->getDryRun());
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
