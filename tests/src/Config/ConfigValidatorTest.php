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

use MoveElevator\ComposerTranslationValidator\Config\ConfigValidator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * ConfigValidatorTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
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

    public function testValidateInvalidPathsType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration 'paths' must be an array");

        $this->validator->validate(['paths' => 'invalid']);
    }

    public function testValidateInvalidValidatorsType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration 'validators' must be an array");

        $this->validator->validate(['validators' => 'invalid']);
    }

    public function testValidateInvalidFileDetectorsType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration 'file-detectors' must be an array");

        $this->validator->validate(['file-detectors' => 'invalid']);
    }

    public function testValidateInvalidParsersType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration 'parsers' must be an array");

        $this->validator->validate(['parsers' => 'invalid']);
    }

    public function testValidateInvalidOnlyType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration 'only' must be an array");

        $this->validator->validate(['only' => 'invalid']);
    }

    public function testValidateInvalidSkipType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration 'skip' must be an array");

        $this->validator->validate(['skip' => 'invalid']);
    }

    public function testValidateInvalidExcludeType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration 'exclude' must be an array");

        $this->validator->validate(['exclude' => 'invalid']);
    }

    public function testValidateInvalidStrictType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration 'strict' must be a boolean");

        $this->validator->validate(['strict' => 'invalid']);
    }

    public function testValidateInvalidDryRunType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration 'dry-run' must be a boolean");

        $this->validator->validate(['dry-run' => 'invalid']);
    }

    public function testValidateInvalidFormatType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration 'format' must be a string");

        $this->validator->validate(['format' => 123]);
    }

    public function testValidateInvalidFormatValue(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Invalid format 'invalid'. Allowed formats: cli, json, yaml, php");

        $this->validator->validate(['format' => 'invalid']);
    }

    public function testValidateInvalidVerboseType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration 'verbose' must be a boolean");

        $this->validator->validate(['verbose' => 'invalid']);
    }

    public function testValidateEmptyConfig(): void
    {
        $this->validator->validate([]);
        $this->addToAssertionCount(1);
    }
}
