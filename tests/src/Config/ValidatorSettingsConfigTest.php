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

        self::assertTrue($config->hasValidatorSettings('KeyNamingConventionValidator'));
        self::assertTrue($config->hasValidatorSettings('HtmlTagValidator'));
        self::assertFalse($config->hasValidatorSettings('NonExistentValidator'));

        $keyNamingSettings = $config->getValidatorSettings('KeyNamingConventionValidator');
        self::assertSame(['convention' => 'snake_case'], $keyNamingSettings);
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

        self::assertTrue($config->hasValidatorSettings('KeyNamingConventionValidator'));

        $keyNamingSettings = $config->getValidatorSettings('KeyNamingConventionValidator');
        self::assertSame(['convention' => 'camelCase'], $keyNamingSettings);
    }

    public function testSetValidatorSettings(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        self::assertFalse($config->hasValidatorSettings('TestValidator'));

        $config->setValidatorSetting('TestValidator', ['key' => 'value']);

        self::assertTrue($config->hasValidatorSettings('TestValidator'));
        self::assertSame(['key' => 'value'], $config->getValidatorSettings('TestValidator'));
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

        self::assertSame($settings, $config->getAllValidatorSettings());
        self::assertSame(['setting1' => 'value1'], $config->getValidatorSettings('Validator1'));
        self::assertSame(['setting2' => 'value2'], $config->getValidatorSettings('Validator2'));
    }

    public function testGetEmptyValidatorSettings(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        self::assertSame([], $config->getValidatorSettings('NonExistentValidator'));
        self::assertSame([], $config->getAllValidatorSettings());
    }

    public function testToArrayIncludesValidatorSettings(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        $config->setValidatorSetting('TestValidator', ['test' => 'value']);
        $array = $config->toArray();

        self::assertArrayHasKey('validator-settings', $array);
        self::assertEquals(['TestValidator' => ['test' => 'value']], $array['validator-settings']);
    }

    public function testHasValidatorSettingsMethod(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        self::assertFalse($config->hasValidatorSettings('NonExistent'));

        $config->setValidatorSetting('Exists', ['key' => 'value']);
        self::assertTrue($config->hasValidatorSettings('Exists'));
        self::assertFalse($config->hasValidatorSettings('StillNonExistent'));
    }

    public function testValidatorSettingsOverride(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        // Set initial settings
        $config->setValidatorSetting('TestValidator', ['initial' => 'value']);
        self::assertSame(['initial' => 'value'], $config->getValidatorSettings('TestValidator'));

        // Override with new settings
        $config->setValidatorSetting('TestValidator', ['new' => 'value', 'additional' => 'setting']);
        $settings = $config->getValidatorSettings('TestValidator');
        // @phpstan-ignore-next-line
        self::assertSame(['new' => 'value', 'additional' => 'setting'], $settings);
        self::assertArrayNotHasKey('initial', $settings);
    }
}
