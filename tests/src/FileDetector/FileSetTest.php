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

namespace MoveElevator\ComposerTranslationValidator\Tests\FileDetector;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use PHPUnit\Framework\TestCase;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

class FileSetTest extends TestCase
{
    public function testConstruct(): void
    {
        $parser = 'XliffParser';
        $path = '/path/to/translations';
        $setKey = 'default';
        $files = ['file1.xlf', 'file2.xlf'];

        $fileSet = new FileSet($parser, $path, $setKey, $files);

        $this->assertSame($parser, $fileSet->getParser());
        $this->assertSame($path, $fileSet->getPath());
        $this->assertSame($setKey, $fileSet->getSetKey());
        $this->assertSame($files, $fileSet->getFiles());
    }

    public function testGetParser(): void
    {
        $fileSet = new FileSet('YamlParser', '/path', 'key', []);

        $this->assertSame('YamlParser', $fileSet->getParser());
    }

    public function testGetPath(): void
    {
        $path = '/some/translation/directory';
        $fileSet = new FileSet('TestParser', $path, 'key', []);

        $this->assertSame($path, $fileSet->getPath());
    }

    public function testGetSetKey(): void
    {
        $setKey = 'messages';
        $fileSet = new FileSet('TestParser', '/path', $setKey, []);

        $this->assertSame($setKey, $fileSet->getSetKey());
    }

    public function testGetFiles(): void
    {
        $files = ['messages.en.yaml', 'messages.de.yaml', 'messages.fr.yaml'];
        $fileSet = new FileSet('YamlParser', '/translations', 'messages', $files);

        $this->assertSame($files, $fileSet->getFiles());
    }

    public function testGetFilesEmpty(): void
    {
        $fileSet = new FileSet('TestParser', '/path', 'key', []);

        $this->assertSame([], $fileSet->getFiles());
    }

    public function testWithRealWorldData(): void
    {
        $parser = \MoveElevator\ComposerTranslationValidator\Parser\XliffParser::class;
        $path = 'tests/src/Fixtures/translations/xliff/success';
        $setKey = 'locallang';
        $files = [
            'tests/src/Fixtures/translations/xliff/success/locallang.xlf',
            'tests/src/Fixtures/translations/xliff/success/de.locallang.xlf',
            'tests/src/Fixtures/translations/xliff/success/fr.locallang.xlf',
        ];

        $fileSet = new FileSet($parser, $path, $setKey, $files);

        $this->assertSame($parser, $fileSet->getParser());
        $this->assertSame($path, $fileSet->getPath());
        $this->assertSame($setKey, $fileSet->getSetKey());
        $this->assertSame($files, $fileSet->getFiles());
        $this->assertGreaterThan(0, count($fileSet->getFiles()));
    }

    public function testWithEmptyStrings(): void
    {
        $fileSet = new FileSet('', '', '', []);

        $this->assertSame('', $fileSet->getParser());
        $this->assertSame('', $fileSet->getPath());
        $this->assertSame('', $fileSet->getSetKey());
        $this->assertSame([], $fileSet->getFiles());
    }
}
