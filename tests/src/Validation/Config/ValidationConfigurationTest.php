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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validation\Config;

use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;
use MoveElevator\ComposerTranslationValidator\Validation\Config\ValidationConfiguration;
use MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidationConfiguration::class)]
class ValidationConfigurationTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $config = new ValidationConfiguration();

        $this->assertSame([], $config->paths);
        $this->assertSame([], $config->onlyValidators);
        $this->assertSame([], $config->skipValidators);
        $this->assertSame([], $config->excludePatterns);
        $this->assertSame([], $config->fileDetectors);
        $this->assertSame([], $config->parsers);
        $this->assertFalse($config->strict);
        $this->assertFalse($config->dryRun);
        $this->assertSame('cli', $config->format);
        $this->assertFalse($config->verbose);
        $this->assertFalse($config->recursive);
        $this->assertSame([], $config->validatorSettings);
    }

    public function testConstructorWithAllParameters(): void
    {
        $config = new ValidationConfiguration(
            paths: ['/test/path'],
            onlyValidators: [MismatchValidator::class],
            skipValidators: [MismatchValidator::class],
            excludePatterns: ['*.tmp'],
            fileDetectors: ['TestDetector'],
            parsers: ['TestParser'],
            strict: true,
            dryRun: true,
            format: 'json',
            verbose: true,
            recursive: true,
            validatorSettings: ['test' => ['key' => 'value']],
        );

        $this->assertSame(['/test/path'], $config->paths);
        $this->assertSame([MismatchValidator::class], $config->onlyValidators);
        $this->assertSame([MismatchValidator::class], $config->skipValidators);
        $this->assertSame(['*.tmp'], $config->excludePatterns);
        $this->assertSame(['TestDetector'], $config->fileDetectors);
        $this->assertSame(['TestParser'], $config->parsers);
        $this->assertTrue($config->strict);
        $this->assertTrue($config->dryRun);
        $this->assertSame('json', $config->format);
        $this->assertTrue($config->verbose);
        $this->assertTrue($config->recursive);
        $this->assertSame(['test' => ['key' => 'value']], $config->validatorSettings);
    }

    public function testFromLegacyConfig(): void
    {
        $legacy = new TranslationValidatorConfig();
        $legacy->setPaths(['/test/path'])
            ->setOnly([MismatchValidator::class])
            ->setSkip([MismatchValidator::class])
            ->setExclude(['*.tmp'])
            ->setFileDetectors(['TestDetector'])
            ->setParsers(['TestParser'])
            ->setStrict(true)
            ->setDryRun(true)
            ->setFormat('json')
            ->setVerbose(true)
            ->setValidatorSettings(['test' => ['key' => 'value']]);

        $config = ValidationConfiguration::fromLegacyConfig($legacy);

        $this->assertSame(['/test/path'], $config->paths);
        $this->assertSame([MismatchValidator::class], $config->onlyValidators);
        $this->assertSame([MismatchValidator::class], $config->skipValidators);
        $this->assertSame(['*.tmp'], $config->excludePatterns);
        $this->assertSame(['TestDetector'], $config->fileDetectors);
        $this->assertSame(['TestParser'], $config->parsers);
        $this->assertTrue($config->strict);
        $this->assertTrue($config->dryRun);
        $this->assertSame('json', $config->format);
        $this->assertTrue($config->verbose);
        $this->assertFalse($config->recursive); // Not available in legacy
        $this->assertSame(['test' => ['key' => 'value']], $config->validatorSettings);
    }

    public function testFromArrayWithLegacyKeys(): void
    {
        $array = [
            'paths' => ['/test/path'],
            'only' => [MismatchValidator::class],
            'skip' => [MismatchValidator::class],
            'exclude' => ['*.tmp'],
            'file-detectors' => ['TestDetector'],
            'parsers' => ['TestParser'],
            'strict' => true,
            'dry-run' => true,
            'format' => 'json',
            'verbose' => true,
            'recursive' => true,
            'validator-settings' => ['test' => ['key' => 'value']],
        ];

        $config = ValidationConfiguration::fromArray($array);

        $this->assertSame(['/test/path'], $config->paths);
        $this->assertSame([MismatchValidator::class], $config->onlyValidators);
        $this->assertSame([MismatchValidator::class], $config->skipValidators);
        $this->assertSame(['*.tmp'], $config->excludePatterns);
        $this->assertSame(['TestDetector'], $config->fileDetectors);
        $this->assertSame(['TestParser'], $config->parsers);
        $this->assertTrue($config->strict);
        $this->assertTrue($config->dryRun);
        $this->assertSame('json', $config->format);
        $this->assertTrue($config->verbose);
        $this->assertTrue($config->recursive);
        $this->assertSame(['test' => ['key' => 'value']], $config->validatorSettings);
    }

    public function testFromArrayWithNewKeys(): void
    {
        $array = [
            'paths' => ['/test/path'],
            'onlyValidators' => [MismatchValidator::class],
            'skipValidators' => [MismatchValidator::class],
            'excludePatterns' => ['*.tmp'],
            'fileDetectors' => ['TestDetector'],
            'parsers' => ['TestParser'],
            'strict' => true,
            'dryRun' => true,
            'format' => 'json',
            'verbose' => true,
            'recursive' => true,
            'validatorSettings' => ['test' => ['key' => 'value']],
        ];

        $config = ValidationConfiguration::fromArray($array);

        $this->assertSame(['/test/path'], $config->paths);
        $this->assertSame([MismatchValidator::class], $config->onlyValidators);
        $this->assertSame([MismatchValidator::class], $config->skipValidators);
        $this->assertSame(['*.tmp'], $config->excludePatterns);
        $this->assertSame(['TestDetector'], $config->fileDetectors);
        $this->assertSame(['TestParser'], $config->parsers);
        $this->assertTrue($config->strict);
        $this->assertTrue($config->dryRun);
        $this->assertSame('json', $config->format);
        $this->assertTrue($config->verbose);
        $this->assertTrue($config->recursive);
        $this->assertSame(['test' => ['key' => 'value']], $config->validatorSettings);
    }

    public function testToLegacyConfig(): void
    {
        $config = new ValidationConfiguration(
            paths: ['/test/path'],
            onlyValidators: [MismatchValidator::class],
            skipValidators: [MismatchValidator::class],
            excludePatterns: ['*.tmp'],
            fileDetectors: ['TestDetector'],
            parsers: ['TestParser'],
            strict: true,
            dryRun: true,
            format: 'json',
            verbose: true,
            validatorSettings: ['test' => ['key' => 'value']],
        );

        $legacy = $config->toLegacyConfig();

        $this->assertSame(['/test/path'], $legacy->getPaths());
        $this->assertSame([MismatchValidator::class], $legacy->getOnly());
        $this->assertSame([MismatchValidator::class], $legacy->getSkip());
        $this->assertSame(['*.tmp'], $legacy->getExclude());
        $this->assertSame(['TestDetector'], $legacy->getFileDetectors());
        $this->assertSame(['TestParser'], $legacy->getParsers());
        $this->assertTrue($legacy->getStrict());
        $this->assertTrue($legacy->getDryRun());
        $this->assertSame('json', $legacy->getFormat());
        $this->assertTrue($legacy->getVerbose());
        $this->assertSame(['test' => ['key' => 'value']], $legacy->getAllValidatorSettings());
    }

    public function testWithMethods(): void
    {
        $config = new ValidationConfiguration();

        $withPaths = $config->withPaths(['/test/path']);
        $this->assertSame(['/test/path'], $withPaths->paths);
        $this->assertSame([], $config->paths); // Original unchanged

        $withOnly = $config->withOnlyValidators([MismatchValidator::class]);
        $this->assertSame([MismatchValidator::class], $withOnly->onlyValidators);
        $this->assertSame([], $config->onlyValidators); // Original unchanged

        $withSkip = $config->withSkipValidators([MismatchValidator::class]);
        $this->assertSame([MismatchValidator::class], $withSkip->skipValidators);
        $this->assertSame([], $config->skipValidators); // Original unchanged

        $withStrict = $config->withStrict(true);
        $this->assertTrue($withStrict->strict);
        $this->assertFalse($config->strict); // Original unchanged

        $withRecursive = $config->withRecursive(true);
        $this->assertTrue($withRecursive->recursive);
        $this->assertFalse($config->recursive); // Original unchanged
    }

    public function testHasMethods(): void
    {
        $emptyConfig = new ValidationConfiguration();
        $this->assertFalse($emptyConfig->hasValidatorsSpecified());
        $this->assertFalse($emptyConfig->hasValidatorsToSkip());
        $this->assertFalse($emptyConfig->hasExcludePatterns());
        $this->assertFalse($emptyConfig->hasCustomFileDetectors());
        $this->assertFalse($emptyConfig->hasCustomParsers());
        $this->assertFalse($emptyConfig->hasValidatorSettings());

        $configWithData = new ValidationConfiguration(
            onlyValidators: [MismatchValidator::class],
            skipValidators: [MismatchValidator::class],
            excludePatterns: ['*.tmp'],
            fileDetectors: ['TestDetector'],
            parsers: ['TestParser'],
            validatorSettings: ['test' => ['key' => 'value']],
        );

        $this->assertTrue($configWithData->hasValidatorsSpecified());
        $this->assertTrue($configWithData->hasValidatorsToSkip());
        $this->assertTrue($configWithData->hasExcludePatterns());
        $this->assertTrue($configWithData->hasCustomFileDetectors());
        $this->assertTrue($configWithData->hasCustomParsers());
        $this->assertTrue($configWithData->hasValidatorSettings());
    }

    public function testGetValidatorSettings(): void
    {
        $config = new ValidationConfiguration(
            validatorSettings: [
                'ValidatorA' => ['key1' => 'value1'],
                'ValidatorB' => ['key2' => 'value2'],
            ],
        );

        $this->assertSame(['key1' => 'value1'], $config->getValidatorSettings('ValidatorA'));
        $this->assertSame(['key2' => 'value2'], $config->getValidatorSettings('ValidatorB'));
        $this->assertSame([], $config->getValidatorSettings('ValidatorC')); // Not found
    }

    public function testToArray(): void
    {
        $config = new ValidationConfiguration(
            paths: ['/test/path'],
            onlyValidators: [MismatchValidator::class],
            skipValidators: [MismatchValidator::class],
            excludePatterns: ['*.tmp'],
            fileDetectors: ['TestDetector'],
            parsers: ['TestParser'],
            strict: true,
            dryRun: true,
            format: 'json',
            verbose: true,
            recursive: true,
            validatorSettings: ['test' => ['key' => 'value']],
        );

        $expected = [
            'paths' => ['/test/path'],
            'onlyValidators' => [MismatchValidator::class],
            'skipValidators' => [MismatchValidator::class],
            'excludePatterns' => ['*.tmp'],
            'fileDetectors' => ['TestDetector'],
            'parsers' => ['TestParser'],
            'strict' => true,
            'dryRun' => true,
            'format' => 'json',
            'verbose' => true,
            'recursive' => true,
            'validatorSettings' => ['test' => ['key' => 'value']],
        ];

        $this->assertSame($expected, $config->toArray());
    }

    public function testImmutability(): void
    {
        $config = new ValidationConfiguration(
            paths: ['/test/path'],
            onlyValidators: [MismatchValidator::class],
        );

        // Try to modify arrays (should not affect the object due to readonly)
        $paths = $config->paths;
        $paths[] = '/another/path';

        $validators = $config->onlyValidators;
        $validators[] = MismatchValidator::class;

        // Original object should be unchanged
        $this->assertSame(['/test/path'], $config->paths);
        $this->assertSame([MismatchValidator::class], $config->onlyValidators);
    }

    public function testFromArrayWithMixedKeys(): void
    {
        $array = [
            'paths' => ['/test/path'],
            'only' => ['ValidatorA'], // legacy key
            'skipValidators' => ['ValidatorB'], // new key
            'exclude' => ['*.tmp'], // legacy key
            'fileDetectors' => ['TestDetector'], // new key
        ];

        $config = ValidationConfiguration::fromArray($array);

        $this->assertSame(['/test/path'], $config->paths);
        $this->assertSame(['ValidatorA'], $config->onlyValidators);
        $this->assertSame(['ValidatorB'], $config->skipValidators);
        $this->assertSame(['*.tmp'], $config->excludePatterns);
        $this->assertSame(['TestDetector'], $config->fileDetectors);
    }

    public function testFromArrayWithInvalidTypes(): void
    {
        $array = [
            'paths' => 'not-an-array',
            'only' => 'not-an-array',
            'strict' => 'not-a-boolean',
            'recursive' => 1, // should be cast to boolean
        ];

        $config = ValidationConfiguration::fromArray($array);

        $this->assertSame([], $config->paths); // Invalid type fallback
        $this->assertSame([], $config->onlyValidators); // Invalid type fallback
        $this->assertTrue($config->strict); // Non-empty string cast to true
        $this->assertTrue($config->recursive); // 1 cast to true
    }

    public function testFromArrayEmpty(): void
    {
        $config = ValidationConfiguration::fromArray([]);

        $this->assertSame([], $config->paths);
        $this->assertSame([], $config->onlyValidators);
        $this->assertSame([], $config->skipValidators);
        $this->assertSame([], $config->excludePatterns);
        $this->assertSame([], $config->fileDetectors);
        $this->assertSame([], $config->parsers);
        $this->assertFalse($config->strict);
        $this->assertFalse($config->dryRun);
        $this->assertSame('cli', $config->format);
        $this->assertFalse($config->verbose);
        $this->assertFalse($config->recursive);
        $this->assertSame([], $config->validatorSettings);
    }

    public function testFromLegacyConfigWithComplexSettings(): void
    {
        $legacy = new TranslationValidatorConfig();
        $legacy->setPaths(['/test/path1', '/test/path2'])
            ->setOnly(['ValidatorA', 'ValidatorB'])
            ->setSkip(['ValidatorC'])
            ->setExclude(['*.tmp', '*.backup'])
            ->setFileDetectors(['DetectorA', 'DetectorB'])
            ->setParsers(['ParserA'])
            ->setStrict(true)
            ->setDryRun(true)
            ->setFormat('json')
            ->setVerbose(true)
            ->setValidatorSettings([
                'ValidatorA' => ['option1' => 'value1', 'option2' => true],
                'ValidatorB' => ['option3' => 123, 'option4' => ['nested', 'array']],
            ]);

        $config = ValidationConfiguration::fromLegacyConfig($legacy);

        $this->assertSame(['/test/path1', '/test/path2'], $config->paths);
        $this->assertSame(['ValidatorA', 'ValidatorB'], $config->onlyValidators);
        $this->assertSame(['ValidatorC'], $config->skipValidators);
        $this->assertSame(['*.tmp', '*.backup'], $config->excludePatterns);
        $this->assertSame(['DetectorA', 'DetectorB'], $config->fileDetectors);
        $this->assertSame(['ParserA'], $config->parsers);
        $this->assertTrue($config->strict);
        $this->assertTrue($config->dryRun);
        $this->assertSame('json', $config->format);
        $this->assertTrue($config->verbose);
        $this->assertFalse($config->recursive); // Not available in legacy
        $this->assertSame([
            'ValidatorA' => ['option1' => 'value1', 'option2' => true],
            'ValidatorB' => ['option3' => 123, 'option4' => ['nested', 'array']],
        ], $config->validatorSettings);
    }

    public function testWithMethodChaining(): void
    {
        $config = new ValidationConfiguration();

        $final = $config
            ->withPaths(['/test/path'])
            ->withOnlyValidators(['ValidatorA'])
            ->withSkipValidators(['ValidatorB'])
            ->withStrict(true)
            ->withRecursive(true);

        // Each step should return a new immutable instance
        $this->assertSame([], $config->paths);
        $this->assertSame(['/test/path'], $final->paths);
        $this->assertSame(['ValidatorA'], $final->onlyValidators);
        $this->assertSame(['ValidatorB'], $final->skipValidators);
        $this->assertTrue($final->strict);
        $this->assertTrue($final->recursive);
    }

    public function testGetValidatorSettingsEdgeCases(): void
    {
        $config = new ValidationConfiguration(
            validatorSettings: [
                'ValidatorA' => [],
                'ValidatorB' => ['key' => 'value'],
            ],
        );

        $this->assertSame([], $config->getValidatorSettings('ValidatorA'));
        $this->assertSame(['key' => 'value'], $config->getValidatorSettings('ValidatorB'));
        $this->assertSame([], $config->getValidatorSettings('NonExistentValidator'));
    }

    public function testHasMethodsEdgeCases(): void
    {
        // Test with empty arrays (should return false)
        $emptyConfig = new ValidationConfiguration(
            onlyValidators: [],
            skipValidators: [],
            excludePatterns: [],
            fileDetectors: [],
            parsers: [],
            validatorSettings: [],
        );

        $this->assertFalse($emptyConfig->hasValidatorsSpecified());
        $this->assertFalse($emptyConfig->hasValidatorsToSkip());
        $this->assertFalse($emptyConfig->hasExcludePatterns());
        $this->assertFalse($emptyConfig->hasCustomFileDetectors());
        $this->assertFalse($emptyConfig->hasCustomParsers());
        $this->assertFalse($emptyConfig->hasValidatorSettings());

        // Test with single items (should return true)
        $singleItemConfig = new ValidationConfiguration(
            onlyValidators: ['single'],
            skipValidators: ['single'],
            excludePatterns: ['single'],
            fileDetectors: ['single'],
            parsers: ['single'],
            validatorSettings: ['single' => []],
        );

        $this->assertTrue($singleItemConfig->hasValidatorsSpecified());
        $this->assertTrue($singleItemConfig->hasValidatorsToSkip());
        $this->assertTrue($singleItemConfig->hasExcludePatterns());
        $this->assertTrue($singleItemConfig->hasCustomFileDetectors());
        $this->assertTrue($singleItemConfig->hasCustomParsers());
        $this->assertTrue($singleItemConfig->hasValidatorSettings());
    }
}
