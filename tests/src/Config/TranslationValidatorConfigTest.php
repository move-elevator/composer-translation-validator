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

use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TranslationValidatorConfig::class)]
class TranslationValidatorConfigTest extends TestCase
{
    private TranslationValidatorConfig $config;

    protected function setUp(): void
    {
        $this->config = new TranslationValidatorConfig();
    }

    public function testSetAndGetPaths(): void
    {
        $paths = ['path1', 'path2'];
        $this->config->setPaths($paths);
        $this->assertSame($paths, $this->config->getPaths());
    }

    public function testAddAndGetValidator(): void
    {
        $validator = 'SomeValidator';
        $this->config->addValidator($validator);
        $this->assertSame([$validator], $this->config->getValidators());
    }

    public function testSetAndGetValidators(): void
    {
        $validators = ['Validator1', 'Validator2'];
        $this->config->setValidators($validators);
        $this->assertSame($validators, $this->config->getValidators());
    }

    public function testAddAndGetFileDetector(): void
    {
        $fileDetector = 'SomeFileDetector';
        $this->config->addFileDetector($fileDetector);
        $this->assertSame([$fileDetector], $this->config->getFileDetectors());
    }

    public function testSetAndGetFileDetectors(): void
    {
        $fileDetectors = ['Detector1', 'Detector2'];
        $this->config->setFileDetectors($fileDetectors);
        $this->assertSame($fileDetectors, $this->config->getFileDetectors());
    }

    public function testAddAndGetParser(): void
    {
        $parser = 'SomeParser';
        $this->config->addParser($parser);
        $this->assertSame([$parser], $this->config->getParsers());
    }

    public function testSetAndGetParsers(): void
    {
        $parsers = ['Parser1', 'Parser2'];
        $this->config->setParsers($parsers);
        $this->assertSame($parsers, $this->config->getParsers());
    }

    public function testOnlyAndGetOnly(): void
    {
        $validator = 'OnlyValidator';
        $this->config->only($validator);
        $this->assertSame([$validator], $this->config->getOnly());
    }

    public function testSetAndGetOnly(): void
    {
        $only = ['Only1', 'Only2'];
        $this->config->setOnly($only);
        $this->assertSame($only, $this->config->getOnly());
    }

    public function testSkipAndGetSkip(): void
    {
        $validator = 'SkipValidator';
        $this->config->skip($validator);
        $this->assertSame([$validator], $this->config->getSkip());
    }

    public function testSetAndGetSkip(): void
    {
        $skip = ['Skip1', 'Skip2'];
        $this->config->setSkip($skip);
        $this->assertSame($skip, $this->config->getSkip());
    }

    public function testSetAndGetExclude(): void
    {
        $exclude = ['vendor/*', 'node_modules/*'];
        $this->config->setExclude($exclude);
        $this->assertSame($exclude, $this->config->getExclude());
    }

    public function testSetAndGetStrict(): void
    {
        $this->config->setStrict(true);
        $this->assertTrue($this->config->getStrict());

        $this->config->setStrict(false);
        $this->assertFalse($this->config->getStrict());
    }

    public function testSetAndGetDryRun(): void
    {
        $this->config->setDryRun(true);
        $this->assertTrue($this->config->getDryRun());

        $this->config->setDryRun(false);
        $this->assertFalse($this->config->getDryRun());
    }

    public function testSetAndGetFormat(): void
    {
        $format = 'json';
        $this->config->setFormat($format);
        $this->assertSame($format, $this->config->getFormat());
    }

    public function testSetAndGetVerbose(): void
    {
        $this->config->setVerbose(true);
        $this->assertTrue($this->config->getVerbose());

        $this->config->setVerbose(false);
        $this->assertFalse($this->config->getVerbose());
    }

    public function testDefaultValues(): void
    {
        $this->assertSame([], $this->config->getPaths());
        $this->assertSame([], $this->config->getValidators());
        $this->assertSame([], $this->config->getFileDetectors());
        $this->assertSame([], $this->config->getParsers());
        $this->assertSame([], $this->config->getOnly());
        $this->assertSame([], $this->config->getSkip());
        $this->assertSame([], $this->config->getExclude());
        $this->assertFalse($this->config->getStrict());
        $this->assertFalse($this->config->getDryRun());
        $this->assertSame('cli', $this->config->getFormat());
        $this->assertFalse($this->config->getVerbose());
    }

    public function testToArray(): void
    {
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

        $expected = [
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
        ];

        $this->assertSame($expected, $this->config->toArray());
    }

    public function testFluentInterface(): void
    {
        $result = $this->config
            ->setPaths(['path1'])
            ->addValidator('Validator1')
            ->addFileDetector('Detector1')
            ->addParser('Parser1')
            ->only('Only1')
            ->skip('Skip1')
            ->setExclude(['vendor/*'])
            ->setStrict(true)
            ->setDryRun(true)
            ->setFormat('json')
            ->setVerbose(true);

        $this->assertSame($this->config, $result);
    }

    public function testToArrayWithAllValues(): void
    {
        $this->config
            ->setPaths(['path1', 'path2'])
            ->setValidators(['validator1', 'validator2'])
            ->setFileDetectors(['detector1'])
            ->setParsers(['parser1'])
            ->setOnly(['only1'])
            ->setSkip(['skip1'])
            ->setExclude(['exclude1'])
            ->setStrict(true)
            ->setDryRun(true)
            ->setFormat('json')
            ->setVerbose(true);

        $expected = [
            'paths' => ['path1', 'path2'],
            'validators' => ['validator1', 'validator2'],
            'file-detectors' => ['detector1'],
            'parsers' => ['parser1'],
            'only' => ['only1'],
            'skip' => ['skip1'],
            'exclude' => ['exclude1'],
            'strict' => true,
            'dry-run' => true,
            'format' => 'json',
            'verbose' => true,
        ];

        $this->assertSame($expected, $this->config->toArray());
    }

    public function testToArrayWithDefaultValues(): void
    {
        $expected = [
            'paths' => [],
            'validators' => [],
            'file-detectors' => [],
            'parsers' => [],
            'only' => [],
            'skip' => [],
            'exclude' => [],
            'strict' => false,
            'dry-run' => false,
            'format' => 'cli',
            'verbose' => false,
        ];

        $this->assertSame($expected, $this->config->toArray());
    }

    public function testMethodChaining(): void
    {
        $result = $this->config
            ->setPaths(['test'])
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

        $this->assertSame($this->config, $result);
        $this->assertSame(['test'], $this->config->getPaths());
        $this->assertSame(['test'], $this->config->getValidators());
        $this->assertSame(['test'], $this->config->getFileDetectors());
        $this->assertSame(['test'], $this->config->getParsers());
        $this->assertSame(['test'], $this->config->getOnly());
        $this->assertSame(['test'], $this->config->getSkip());
        $this->assertSame(['test'], $this->config->getExclude());
        $this->assertTrue($this->config->getStrict());
        $this->assertTrue($this->config->getDryRun());
        $this->assertSame('json', $this->config->getFormat());
        $this->assertTrue($this->config->getVerbose());
    }
}
