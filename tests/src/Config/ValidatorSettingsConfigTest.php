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

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

class ValidatorSettingsConfigTest extends TestCase
{
    public function testLoadValidatorSettingsFromYaml(): void
    {
        $factory = new ConfigFactory();
        $configData = [
            'paths' => ['translations/'],
            'validator-settings' => [
                'KeyNamingConventionValidator' => [
                    'convention' => 'snake_case',
                ],
                'HtmlTagValidator' => [
                    'strict_attributes' => false,
                ],
            ],
        ];

        $config = $factory->createFromArray($configData);

        $this->assertTrue($config->hasValidatorSettings('KeyNamingConventionValidator'));
        $this->assertTrue($config->hasValidatorSettings('HtmlTagValidator'));
        $this->assertFalse($config->hasValidatorSettings('NonExistentValidator'));

        $keyNamingSettings = $config->getValidatorSettings('KeyNamingConventionValidator');
        $this->assertSame(['convention' => 'snake_case'], $keyNamingSettings);
    }

    public function testLoadValidatorSettingsFromJson(): void
    {
        $factory = new ConfigFactory();
        $configData = [
            'paths' => ['translations/'],
            'validator-settings' => [
                'KeyNamingConventionValidator' => [
                    'convention' => 'camelCase',
                ],
            ],
        ];

        $config = $factory->createFromArray($configData);

        $this->assertTrue($config->hasValidatorSettings('KeyNamingConventionValidator'));

        $keyNamingSettings = $config->getValidatorSettings('KeyNamingConventionValidator');
        $this->assertSame(['convention' => 'camelCase'], $keyNamingSettings);
    }

    public function testSetValidatorSettings(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        $this->assertFalse($config->hasValidatorSettings('TestValidator'));

        $config->setValidatorSetting('TestValidator', ['key' => 'value']);

        $this->assertTrue($config->hasValidatorSettings('TestValidator'));
        $this->assertSame(['key' => 'value'], $config->getValidatorSettings('TestValidator'));
    }

    public function testSetMultipleValidatorSettings(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        $settings = [
            'Validator1' => ['setting1' => 'value1'],
            'Validator2' => ['setting2' => 'value2'],
        ];

        $config->setValidatorSettings($settings);

        $this->assertSame($settings, $config->getAllValidatorSettings());
        $this->assertSame(['setting1' => 'value1'], $config->getValidatorSettings('Validator1'));
        $this->assertSame(['setting2' => 'value2'], $config->getValidatorSettings('Validator2'));
    }

    public function testGetEmptyValidatorSettings(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        $this->assertSame([], $config->getValidatorSettings('NonExistentValidator'));
        $this->assertSame([], $config->getAllValidatorSettings());
    }

    public function testToArrayIncludesValidatorSettings(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        $config->setValidatorSetting('TestValidator', ['test' => 'value']);
        $array = $config->toArray();

        $this->assertArrayHasKey('validator-settings', $array);
        $this->assertEquals(['TestValidator' => ['test' => 'value']], $array['validator-settings']);
    }

    public function testHasValidatorSettingsMethod(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        $this->assertFalse($config->hasValidatorSettings('NonExistent'));

        $config->setValidatorSetting('Exists', ['key' => 'value']);
        $this->assertTrue($config->hasValidatorSettings('Exists'));
        $this->assertFalse($config->hasValidatorSettings('StillNonExistent'));
    }

    public function testValidatorSettingsOverride(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        // Set initial settings
        $config->setValidatorSetting('TestValidator', ['initial' => 'value']);
        $this->assertSame(['initial' => 'value'], $config->getValidatorSettings('TestValidator'));

        // Override with new settings
        $config->setValidatorSetting('TestValidator', ['new' => 'value', 'additional' => 'setting']);
        $settings = $config->getValidatorSettings('TestValidator');
        // @phpstan-ignore-next-line
        $this->assertSame(['new' => 'value', 'additional' => 'setting'], $settings);
        $this->assertArrayNotHasKey('initial', $settings);
    }
}
