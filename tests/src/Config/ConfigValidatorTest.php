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

use MoveElevator\ComposerTranslationValidator\Config\ConfigValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * ConfigValidatorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ConfigValidatorTest extends TestCase
{
    private ConfigValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ConfigValidator();
    }

    public function testValidateValidConfig(): void
    {
        $validConfig = [
            'paths' => ['translations/'],
            'validators' => ['SomeValidator'],
            'file-detectors' => ['SomeDetector'],
            'parsers' => ['SomeParser'],
            'only' => ['OnlyValidator'],
            'skip' => ['SkipValidator'],
            'exclude' => ['*.backup'],
            'strict' => true,
            'dry-run' => false,
            'format' => 'json',
            'verbose' => false,
        ];

        $this->validator->validate($validConfig);
        $this->addToAssertionCount(1);
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('invalidTypeProvider')]
    public function testValidateInvalidType(array $config, string $expectedMessage): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->validator->validate($config);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidTypeProvider(): iterable
    {
        yield 'paths' => [['paths' => 'invalid'], "Configuration 'paths' must be an array"];
        yield 'validators' => [['validators' => 'invalid'], "Configuration 'validators' must be an array"];
        yield 'file-detectors' => [['file-detectors' => 'invalid'], "Configuration 'file-detectors' must be an array"];
        yield 'parsers' => [['parsers' => 'invalid'], "Configuration 'parsers' must be an array"];
        yield 'only' => [['only' => 'invalid'], "Configuration 'only' must be an array"];
        yield 'skip' => [['skip' => 'invalid'], "Configuration 'skip' must be an array"];
        yield 'exclude' => [['exclude' => 'invalid'], "Configuration 'exclude' must be an array"];
        yield 'strict' => [['strict' => 'invalid'], "Configuration 'strict' must be a boolean"];
        yield 'dry-run' => [['dry-run' => 'invalid'], "Configuration 'dry-run' must be a boolean"];
        yield 'format type' => [['format' => 123], "Configuration 'format' must be a string"];
        yield 'format value' => [['format' => 'invalid'], "Invalid format 'invalid'. Allowed formats: cli, json, yaml, php"];
        yield 'verbose' => [['verbose' => 'invalid'], "Configuration 'verbose' must be a boolean"];
    }

    public function testValidateEmptyConfig(): void
    {
        $this->validator->validate([]);
        $this->addToAssertionCount(1);
    }
}
