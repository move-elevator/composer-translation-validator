<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Config;

use MoveElevator\ComposerTranslationValidator\Config\ConfigFactory;
use PHPUnit\Framework\TestCase;

/**
 * ValidatorSettingsConfigTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ValidatorSettingsConfigTest extends TestCase
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
