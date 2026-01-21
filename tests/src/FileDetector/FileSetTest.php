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

namespace MoveElevator\ComposerTranslationValidator\Tests\FileDetector;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use PHPUnit\Framework\TestCase;

use function count;

/**
 * FileSetTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class FileSetTest extends TestCase
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
